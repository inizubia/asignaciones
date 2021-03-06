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
    public function homeAction()
    {
        return $this->render('IZJUserBundle:User:home.html.twig');
    }

    public function indexAction(Request $request)
    {
       
        // $users = $em->getRepository('IZJUserBundle:User')->findAll();

        /*
        $res = 'Lista de Usuarios: <br />';

        foreach($users as $user)
        {
        	$res .= 'Usuarios: '. $user-> getUserName() . ' - Email: '. $user->getEmail() . '<br />';
        }

        return new Response($res);
        */

        $searchQuery = $request->get('query');

        if(!empty($searchQuery))
        {
            $finder = $this->container->get('fos_elastica.finder.app.user');
            $users = $finder->createPaginatorAdapter($searchQuery);
        }
        else
        {
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT u FROM IZJUserBundle:User u ORDER BY u.id DESC";
            $users = $em->createQuery($dql);
        }
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
        	$users, $request->query->getInt('page',1),
        	5
        );

        $deleteFormAjax = $this->createCustomForm(':USER_ID', 'DELETE', 'izj_user_delete');

        return $this->render('IZJUserBundle:User:index.html.twig', array('pagination' => $pagination,
            'delete_form_ajax'=> $deleteFormAjax->createView()));
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

        $deleteForm = $this->createCustomForm($user->getId(), 'DELETE', 'izj_user_delete');
       
        return $this->render('IZJUserBundle:User:view.html.twig', array('user' => $user, 'delete_form' => $deleteForm->createView()));       
    }

    /*private function createDeleteForm($user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('izj_user_delete', array('id'=>$user->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }*/

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('IZJUserBundle:User')->find($id);

        if(!$user)
        {
            $messageException = $this->get('translator')->trans('User not found.');
            throw  $this->createNotFoundException($messageException);
        }

        $allUsers = $em->getRepository('IZJUserBundle:User')->findAll();
        $countUsers = count($allUsers);

        //$form = $this->createDeleteForm($user);
        $form = $this->createCustomForm($user->getId(), 'DELETE', 'izj_user_delete');
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            if($request->isXMLHttpRequest())
            {
                $res = $this->deleteUser($user->getRole(), $em, $user);

                return new Response(
                        json_encode(array('removed' => $res['removed'], 'message' => $res['message'], 'countUsers' => $countUsers)),
                        200,
                        array('Content-Type' => 'application/json')
                );
            }

            /*$em->remove($user);
            $em->flush();

            $successMessage = $this->get('translator')->trans('The user has been deleted.');*/

            $res = $this->deleteUser($user->getRole(), $em, $user);

            $this->addFlash($res['alert'], $res['message']);
            return $this->redirectToRoute('izj_user_index');
        }
    }

    private function deleteUser($role, $em, $user)
    {
        if($role == 'ROLE_USER')
        {
            $em->remove($user);
            $em->flush();

            $message = $this->get('translator')->trans('The user has been deleted.');
            $removed = 1;
            $alert = 'mensaje';
        }
        elseif($role == 'ROLE_ADMIN')
        {
            $message = $this->get('translator')->trans('The user could not be deleted.');
            $removed = 0;
            $alert = 'error';
        }

        return array('removed' => $removed, 'message' => $message, 'alert'=> $alert);
    }

    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id'=> $id)))
            ->setMethod($method)
            ->getForm();
    }  
}