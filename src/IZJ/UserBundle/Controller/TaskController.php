<?php

namespace IZJ\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use IZJ\UserBundle\Entity\Task;
use IZJ\UserBundle\Form\TaskType;

class TaskController extends Controller
{

	public function indexAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$dql = "SELECT t FROM IZJUserBundle:Task t ORDER BY t.id DESC";
		$tasks = $em->createQuery($dql);

		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
				$tasks,
				$request->query->getInt('page',1),
				5
			);

		return $this->render('IZJUserBundle:Task:index.html.twig', array('pagination' => $pagination));
	}

	public function addAction()
	{
		$task = new Task();
		$form = $this->createCreateForm($task);

		return $this->render('IZJUserBundle:Task:add.html.twig', array('form' => $form->createView()));
	}

	private function createCreateForm(Task $entity)
	{
		$form = $this->createForm(TaskType::class, $entity, array(
				'action' => $this->generateUrl('izj_task_create'),
				'method' => 'POST'
			));

		return $form;
	}

	public function createAction(Request $request)
	{
		$task = new Task();
		$form = $this->createCreateForm($task);
		$form->handleRequest($request);

		if($form->isValid())
		{
			$task->setStatus(0);
			$em = $this->getDoctrine()->getManager();
			$em->persist($task);
			$em->flush();

			$successMessage = $this->get('translator')->trans('The task has been created.');
			$this->addFlash('mensaje', $successMessage);
			return $this->redirectToRoute('izj_task_index');
		}

		return $this-render('IZJUserBundle:Task:add.html.twig', array('form' => $form->createView()));
	}
}