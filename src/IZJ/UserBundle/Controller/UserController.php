<?php

namespace IZJ\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use IZJ\UserBundle\Entity\User;
use IZJ\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('IZJUserBundle:User')->findAll();

        /*
        $res = 'Lista de Usuarios: <br />';

        foreach($users as $user)
        {
        	$res .= 'Usuarios: '. $user-> getUserName() . ' - Email: '. $user->getEmail() . '<br />';
        }

        return new Response($res);
        */

        return $this->render('IZJUserBundle:User:index.html.twig', array('users' => $users));
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

        	$encoder = $this->container->get('security.password_encoder');
        	$encoded = $encoder->encodePassword($user, $password);

        	$user->setPassword($encoded);

        	$em = $this->getDoctrine()->getManager();
        	$em->persist($user);
        	$em->flush();

        	return $this->redirectToRoute('izj_user_index');
    	}

    	return $this->render('IZJUserBundle:User:add.html.twig', array('form'=> $form->createView()));
    }

    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getRepository('IZJUserBundle:User');

        /*
        user = $repository->find($id);

        //$user = $repository->findOneById($id);

        //$user = $repository->findOneByUsername($nombre);

        return new Response('Usuario: ' . $user->getUserName() . ' con email: ' . $user->getEmail());
        */
    }
}