<?php

namespace App\Controller\Editor;

use App\Entity\Joanna\JoannaReference;
use App\Entity\Joanna\JoannaWork;
use App\Entity\Bible\Book;
use App\Entity\User;
use App\Enum\ReferenceType;
use App\Form\JoannaReferenceType;
use App\Repository\Joanna\JoannaReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor/joanna/reference')]
#[IsGranted('ROLE_EDITOR')]
final class JoannaReferenceController extends AbstractController
{
    private const ROUTE_PREFIX = 'app_editor_joanna_reference';

    #[Route('/', name: 'app_editor_joanna_reference_index', methods: ['GET'])]
    public function index(JoannaReferenceRepository $joannaReferenceRepository): Response
    {
        return $this->render('joanna_reference/index.html.twig', [
            'joanna_references' => $joannaReferenceRepository->findBy(['createdBy' => $this->getUser()], ['id' => 'DESC']),
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'editor/layout.html.twig',
            'can_manage' => true,
        ]);
    }

    #[Route('/all', name: 'app_editor_joanna_reference_all', methods: ['GET'])]
    public function all(Request $request, JoannaReferenceRepository $joannaReferenceRepository, EntityManagerInterface $em): Response
    {
        // Obter parâmetros de filtro
        $bibleBookId = $request->query->get('bible_book');
        $joannaWorkId = $request->query->get('joanna_work');
        $createdById = $request->query->get('created_by');
        $referenceType = $request->query->get('reference_type');

        // Construir query com filtros
        $qb = $joannaReferenceRepository->createQueryBuilder('r')
            ->leftJoin('r.work', 'w')
            ->leftJoin('r.bibleBook', 'b')
            ->leftJoin('r.createdBy', 'u')
            ->orderBy('r.id', 'DESC');

        if ($bibleBookId) {
            $qb->andWhere('r.bibleBook = :bibleBook')
               ->setParameter('bibleBook', $bibleBookId);
        }

        if ($joannaWorkId) {
            $qb->andWhere('r.work = :work')
               ->setParameter('work', $joannaWorkId);
        }

        if ($createdById) {
            $qb->andWhere('r.createdBy = :createdBy')
               ->setParameter('createdBy', $createdById);
        }

        if ($referenceType) {
            $qb->andWhere('r.referenceType = :referenceType')
               ->setParameter('referenceType', $referenceType);
        }

        $references = $qb->getQuery()->getResult();

        // Obter listas para os filtros - livros ordenados por testamento e ordem bíblica
        $bibleBooks = $em->getRepository(Book::class)->createQueryBuilder('b')
            ->leftJoin('b.testament', 't')
            ->orderBy('t.id', 'ASC')
            ->addOrderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();
        $joannaWorks = $em->getRepository(JoannaWork::class)->findBy([], ['title' => 'ASC']);
        $users = $em->getRepository(User::class)->findBy([], ['name' => 'ASC']);
        $referenceTypes = ReferenceType::cases();

        return $this->render('joanna_reference/index.html.twig', [
            'joanna_references' => $references,
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'editor/layout.html.twig',
            'can_manage' => true,
            'show_filters' => true,
            // Filter data
            'bible_books' => $bibleBooks,
            'joanna_works' => $joannaWorks,
            'users' => $users,
            'reference_types' => $referenceTypes,
            // Current filter values
            'current_bible_book' => $bibleBookId,
            'current_joanna_work' => $joannaWorkId,
            'current_created_by' => $createdById,
            'current_reference_type' => $referenceType,
        ]);
    }

    #[Route('/export', name: 'app_editor_joanna_reference_export', methods: ['GET'])]
    public function export(Request $request, JoannaReferenceRepository $joannaReferenceRepository): StreamedResponse
    {
        // Aplicar mesmos filtros do all()
        $bibleBookId = $request->query->get('bible_book');
        $joannaWorkId = $request->query->get('joanna_work');
        $createdById = $request->query->get('created_by');
        $referenceType = $request->query->get('reference_type');

        $qb = $joannaReferenceRepository->createQueryBuilder('r')
            ->leftJoin('r.work', 'w')
            ->leftJoin('r.bibleBook', 'b')
            ->leftJoin('r.createdBy', 'u')
            ->orderBy('r.id', 'DESC');

        if ($bibleBookId) {
            $qb->andWhere('r.bibleBook = :bibleBook')
               ->setParameter('bibleBook', $bibleBookId);
        }

        if ($joannaWorkId) {
            $qb->andWhere('r.work = :work')
               ->setParameter('work', $joannaWorkId);
        }

        if ($createdById) {
            $qb->andWhere('r.createdBy = :createdBy')
               ->setParameter('createdBy', $createdById);
        }

        if ($referenceType) {
            $qb->andWhere('r.referenceType = :referenceType')
               ->setParameter('referenceType', $referenceType);
        }

        $references = $qb->getQuery()->getResult();

        $response = new StreamedResponse(function() use ($references) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM para Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Cabeçalho
            fputcsv($handle, [
                'Livro Bíblico',
                'Capítulo Bíblico',
                'Verso Inicial',
                'Verso Final',
                'Obra (Joanna)',
                'Capítulo (Joanna)',
                'Tipo de Referência',
                'Citação Textual'
            ], ';');

            // Dados
            foreach ($references as $ref) {
                fputcsv($handle, [
                    $ref->getBibleBook()?->getName() ?? '',
                    $ref->getBibleChapter() ?? '',
                    $ref->getBibleVerseStart() ?? '',
                    $ref->getBibleVerseEnd() ?? '',
                    $ref->getWork()?->getTitle() ?? '',
                    $ref->getJoannaChapter() ?? '',
                    $ref->getReferenceType()?->value ?? '',
                    $ref->getCitation() ?? ''
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="referencias_joanna_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_editor_joanna_reference_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $joannaReference = new JoannaReference();
        $form = $this->createForm(JoannaReferenceType::class, $joannaReference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $joannaReference->setCreatedBy($this->getUser());
            $entityManager->persist($joannaReference);
            $entityManager->flush();

            return $this->redirectToRoute('app_editor_joanna_reference_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('joanna_reference/new.html.twig', [
            'joanna_reference' => $joannaReference,
            'form' => $form,
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'editor/layout.html.twig',
        ]);
    }

    #[Route('/{id}', name: 'app_editor_joanna_reference_show', methods: ['GET'])]
    public function show(JoannaReference $joannaReference): Response
    {
        return $this->render('joanna_reference/show.html.twig', [
            'joanna_reference' => $joannaReference,
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'editor/layout.html.twig',
            'can_manage' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_editor_joanna_reference_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JoannaReference $joannaReference, EntityManagerInterface $entityManager): Response
    {
        if ($joannaReference->getCreatedBy() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Você só pode editar suas próprias referências.');
        }

        $form = $this->createForm(JoannaReferenceType::class, $joannaReference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_editor_joanna_reference_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('joanna_reference/edit.html.twig', [
            'joanna_reference' => $joannaReference,
            'form' => $form,
            'route_prefix' => self::ROUTE_PREFIX,
            'layout' => 'editor/layout.html.twig',
        ]);
    }

    #[Route('/{id}', name: 'app_editor_joanna_reference_delete', methods: ['POST'])]
    public function delete(Request $request, JoannaReference $joannaReference, EntityManagerInterface $entityManager): Response
    {
         if ($joannaReference->getCreatedBy() !== $this->getUser()) {
             throw $this->createAccessDeniedException('Você só pode apagar suas próprias referências.');
        }

        if ($this->isCsrfTokenValid('delete'.$joannaReference->getId(), $request->request->get('_token'))) {
            $entityManager->remove($joannaReference);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_editor_joanna_reference_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/approve', name: 'app_editor_joanna_reference_approve', methods: ['POST'])]
    public function approve(Request $request, JoannaReference $joannaReference, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Validar CSRF
        if (!$this->isCsrfTokenValid('approve' . $joannaReference->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
        }

        // Verificar se pode aprovar
        if (!$joannaReference->canUserApprove($user)) {
            if ($joannaReference->getCreatedBy() === $user) {
                $this->addFlash('error', 'Você não pode aprovar sua própria referência.');
            } else {
                $this->addFlash('error', 'Você já aprovou esta referência.');
            }
            return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
        }

        // Criar aprovação
        $approval = new \App\Entity\Joanna\ReferenceApproval();
        $approval->setReference($joannaReference);
        $approval->setApprovedBy($user);
        
        $entityManager->persist($approval);
        $entityManager->flush();

        $this->addFlash('success', 'Referência aprovada com sucesso!');

        return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
    }

    #[Route('/{id}/unapprove', name: 'app_editor_joanna_reference_unapprove', methods: ['POST'])]
    public function unapprove(Request $request, JoannaReference $joannaReference, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Validar CSRF
        if (!$this->isCsrfTokenValid('unapprove' . $joannaReference->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
        }

        // Buscar a aprovação
        $approval = $joannaReference->getUserApproval($user);

        if (!$approval) {
            $this->addFlash('error', 'Você ainda não aprovou esta referência.');
            return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
        }

        // Remover aprovação
        $entityManager->remove($approval);
        $entityManager->flush();

        $this->addFlash('success', 'Aprovação removida com sucesso!');

        return $this->redirectToRoute('app_editor_joanna_reference_show', ['id' => $joannaReference->getId()]);
    }
}
