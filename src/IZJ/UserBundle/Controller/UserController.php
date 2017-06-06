<?php

namespace IZJ\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use IZJ\UserBundle\Entity\User;
use IZJ\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // $users = $em->getRepository('IZJUserBundle:User')->findAll();

        /*
        $res = 'Lista de Usuarios: <br />';

        foreach($users as $user)
        {
        	$res .= 'Usuarios: '. $user-> getUserName() . ' - Email: '. $user->getEmail() . '<br />';
        }

        return new Response($res);
        */

        $dql = "SELECT u FROM IZJUserBundle:User u ORDER BY u.id DESC";
        $users = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
        	$users, $request->query->getInt('page',1),
        	3
        );

        return $this->render('IZJUserBundle:User:index.html.twig', array('pagination' => $pagination));
    }

    public function addAction()
    {
        $user = new User();
        $form = $this->createCreateForm($user);

        return $this->render('IZJUserBundle:User:add.html.twig', array('form' => $form->createView()));
    }

    private function createCreateForm(User $item)
    {
        /*$form = $this->createForm(new UserType(), $item, array(
                'action' => $this->generateUrl('izj_user_create'),
                'method' => 'POST'
            ));*/
        $form = $this->createForm(UserType::class, $item, array(
                'action' => $this->generateUrl('izj_user_create'),
                'method' => 'POST'
            ));
        return $form;
    }

    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);

        if($form->isValid())
        {
        	$password = $form->get('password')->getData();

            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);

            if(count($errorList) == 0)
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);

                $user->setPassword($encoded);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $successMessage = $this->get('translator')->trans('The user has been created.');
                $this->addFlash('mensaje',$successMessage);

                return $this->redirectToRoute('izj_user_index');
            }
            else
            {
                $errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($errorMessage);
            }

    	}

    	return $this->render('IZJUserBundle:User:add.html.twig', array('form'=> $form->createView()));
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('IZJUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw  $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);

        return $this->render('IZJUserBundle:User:edit.html.twig', array('user'=> $user, 'form'=> $form->createView()));
    }

    private function createEditForm(User $item)
    {
            $form = $this->createForm(UserType::class, $item, array(
                'action'=> $this->generateUrl('izj_user_update', array('id' => $item->getId())),
                'method' => 'PUT'));

            return $form;
    }

    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('IZJUserBundle:User')->find($id);

         if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw  $this->createNotFoundException($messageException);
        }

        $form = $this->createEditForm($user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $password = $form->get('password')->getData();
            if(!empty($password))
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }
            else
            {
                $recoverPass = $this->recoverPass($id);
                //print_r($recoverPass);
                //exit();
                $user->setPassword($recoverPass[0]['password']);                
            }

            if($form->get('role')->getData() == 'ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }

            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been modified.');
            $this->addFlash('mensaje', $successMessage);
            return $this->redirectToRoute('izj_user_edit', array('id'=> $user->getId()));
        }
        return $this->render('IZJUserBundle:User:edit.html.twig', array('user' => $user, 'form'=> $form->createView()));
    }

    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT u.password
            FROM IZJUserBundle:User u
            WHERE u.id = :id'    
        )->setParameter('id', $id);
        
        $currentPass = $query->getResult();
        
        return $currentPass;
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('IZJUserBundle:User');
     
        $user = $repository->find($id);

        //$user = $repository->findOneById($id);
        //$user = $repository->findOneByUsername($nombre);
        //return new Response('Usuario: ' . $user->getUserName() . ' con email: ' . $user->getEmail());

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw  $this->createNotFoundException($messageException);
        }

        $deleteForm = $this->createDeleteForm($user);
       
        return $this->render('IZJUserBundle:User:view.html.twig', array('user' => $user, 'delete_form'=>$deleteForm->createView()));       
    }

    private function createDeleteForm($user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('izj_user_delete', array('id'=>$user->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('IZJUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw  $this->createNotFoundException($messageException);
        }

        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em->remove($user);
            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been deleted.');
            $this->addFlash('mensaje', $successMessage);
            return $this->redirectToRoute('izj_user_index');
        }

    }   
}