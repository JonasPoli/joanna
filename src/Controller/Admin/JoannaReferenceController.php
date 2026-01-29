<?php

namespace App\Controller\Admin;

use App\Entity\Joanna\JoannaReference;
use App\Form\JoannaReferenceType;
use App\Repository\Joanna\JoannaReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/joanna/reference')]
#[IsGranted('ROLE_DEV')]
final class JoannaReferenceController extends AbstractController
{
    private const ROUTE_PREFIX = 'app_admin_joanna_reference';

    #[Route('/', name: 'app_admin_joanna_reference_index', methods: ['GET'])]
    public function index(JoannaReferenceRepository $joannaReferenceRepository): Response
    {
        return $this->render('joanna_reference/index.html.twig', [
            'joanna_references' => $joannaReferenceRepository->findAll(),
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'admin/layout.html.twig',
            'can_manage' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_joanna_reference_show', methods: ['GET'])]
    public function show(JoannaReference $joannaReference): Response
    {
        return $this->render('joanna_reference/show.html.twig', [
            'joanna_reference' => $joannaReference,
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'admin/layout.html.twig',
            'can_manage' => false,
        ]);
    }
}
