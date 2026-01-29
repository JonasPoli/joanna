<?php

namespace App\Controller\Api;

use App\Entity\Bible\Book;
use App\Entity\Bible\Verse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bible', name: 'api_bible_')]
class BibleController extends AbstractController
{
    #[Route('/book/{id}/chapters', name: 'chapters', methods: ['GET'])]
    public function getChapters(Book $book, EntityManagerInterface $entityManager): JsonResponse
    {
        // Get distinct chapters for this book
        $query = $entityManager->createQuery(
            'SELECT DISTINCT v.chapter 
            FROM App\Entity\Bible\Verse v 
            WHERE v.book = :book 
            ORDER BY v.chapter ASC'
        )->setParameter('book', $book);

        $chapters = array_column($query->getResult(), 'chapter');

        return $this->json($chapters);
    }

    #[Route('/book/{id}/chapter/{chapter}/verses', name: 'verses', methods: ['GET'])]
    public function getVerses(Book $book, int $chapter, EntityManagerInterface $entityManager): JsonResponse
    {
        // Get max verse for this book and chapter
        $query = $entityManager->createQuery(
            'SELECT MAX(v.verse) as max_verse 
            FROM App\Entity\Bible\Verse v 
            WHERE v.book = :book AND v.chapter = :chapter'
        )
        ->setParameter('book', $book)
        ->setParameter('chapter', $chapter);

        $result = $query->getOneOrNullResult();
        $maxVerse = $result ? (int) $result['max_verse'] : 0;

        return $this->json(['max_verse' => $maxVerse]);
    }

    #[Route('/book/{id}/chapter/{chapter}/text', name: 'text', methods: ['GET'])]
    public function getVerseText(
        Book $book, 
        int $chapter, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $start = $request->query->getInt('start', 1);
        $end = $request->query->get('end') ? $request->query->getInt('end') : null;
        $versionId = $request->query->getInt('version', 1);

        $qb = $entityManager->createQueryBuilder();
        $qb->select('v')
            ->from(Verse::class, 'v')
            ->where('v.book = :book')
            ->andWhere('v.chapter = :chapter')
            ->andWhere('v.version = :version')
            ->andWhere('v.verse >= :start')
            ->setParameter('book', $book)
            ->setParameter('chapter', $chapter)
            ->setParameter('version', $versionId)
            ->setParameter('start', $start)
            ->orderBy('v.verse', 'ASC');

        if ($end) {
            $qb->andWhere('v.verse <= :end')
               ->setParameter('end', $end);
        }

        $verses = $qb->getQuery()->getResult();
        
        // Remove duplicates by verse number
        $seen = [];
        $data = [];
        foreach ($verses as $v) {
            $verseNum = $v->getVerse();
            if (!isset($seen[$verseNum])) {
                $seen[$verseNum] = true;
                $data[] = [
                    'verse' => $verseNum,
                    'text' => $v->getText()
                ];
            }
        }

        return $this->json($data);
    }
}
