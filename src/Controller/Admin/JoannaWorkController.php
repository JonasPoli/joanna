<?php

namespace App\Controller\Admin;

use App\Entity\Joanna\JoannaWork;
use App\Form\JoannaWorkType;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/joanna/work')]
#[IsGranted('ROLE_DEV')]
final class JoannaWorkController extends AbstractController
{
    #[Route('/', name: 'app_admin_joanna_work_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('admin/joanna_work/index.html.twig', [
            'joanna_works' => $entityManager->getRepository(JoannaWork::class)->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_joanna_work_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $joannaWork = new JoannaWork();
        $form = $this->createForm(JoannaWorkType::class, $joannaWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($joannaWork);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_joanna_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/joanna_work/new.html.twig', [
            'joanna_work' => $joannaWork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_joanna_work_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JoannaWork $joannaWork, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JoannaWorkType::class, $joannaWork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_joanna_work_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/joanna_work/edit.html.twig', [
            'joanna_work' => $joannaWork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_joanna_work_delete', methods: ['POST'])]
    public function delete(Request $request, JoannaWork $joannaWork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$joannaWork->getId(), $request->request->get('_token'))) {
            $entityManager->remove($joannaWork);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_joanna_work_index', [], Response::HTTP_SEE_OTHER);
    }
}
