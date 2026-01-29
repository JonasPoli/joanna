<?php

namespace App\Command;

use App\Entity\Bible\Book;
use App\Entity\Joanna\JoannaReference;
use App\Entity\Joanna\JoannaWork;
use App\Entity\Joanna\ReferenceApproval;
use App\Entity\User;
use App\Enum\ReferenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-approvals',
    description: 'Import approvals from CSV for Denise Lino (ID 5)',
)]
class ImportApprovalsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = 5;

        // 1. Buscar usuário Denise
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            $io->error("Usuário com ID $userId não encontrado.");
            return Command::FAILURE;
        }

        $io->info("Usuário encontrado: " . $user->getName() . " (ID: $userId)");

        // 2. Abrir CSV (caminho relativo à raiz do projeto ou absoluto fornecido)
        $csvPath = 'docs/aprovados.csv'; // Assumindo rodar da raiz
        if (!file_exists($csvPath)) {
            $io->error("Arquivo CSV não encontrado em: $csvPath");
            return Command::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $io->error("Não foi possível abrir o arquivo CSV.");
            return Command::FAILURE;
        }

        // Ler cabeçalho
        $header = fgetcsv($handle);
        // Esperado: livro,Capítulo,Verso inicial,Verso final,Obra,Capítulo,Tipo de Referência,Citação

        $count = 0;
        $created = 0;
        $skipped = 0;
        $notFound = 0;

        $joannaWorkRepo = $this->entityManager->getRepository(JoannaWork::class);
        $bookRepo = $this->entityManager->getRepository(Book::class);
        $referenceRepo = $this->entityManager->getRepository(JoannaReference::class);

        // Cache simples para evitar queries repetidas de obras e livros
        $worksCache = [];
        $booksCache = [];

        while (($data = fgetcsv($handle)) !== false) {
            $count++;
            
            // Mapear colunas (ajustar índices conforme CSV)
            // 0: Livro (Bíblia/Mateus)
            // 1: Capítulo (Bíblia/5)
            // 2: Verso inicial (9)
            // 3: Verso final (9)
            // 4: Obra (Alegria de Viver)
            // 5: Capítulo (Joanna/6 - A força...)
            // 6: Tipo (Epígrafe)
            
            $bibleBookName = trim($data[0]);
            
            // Correções de digitação
            $typoMap = [
                'Filipensens' => 'Filipenses',
            ];
            if (isset($typoMap[$bibleBookName])) {
                $bibleBookName = $typoMap[$bibleBookName];
            }

            $bibleChapter = (int) trim($data[1]);
            $bibleVerseStart = (int) trim($data[2]);
            $bibleVerseEnd = (int) trim($data[3]);
            $workTitle = trim($data[4]);
            $joannaChapter = trim($data[5]);
            $typeStr = trim($data[6]);

            // Buscar Livro
            if (!isset($booksCache[$bibleBookName])) {
                $book = $bookRepo->findOneBy(['name' => $bibleBookName]);
                $booksCache[$bibleBookName] = $book;
            }
            $book = $booksCache[$bibleBookName];

            if (!$book) {
                // Tentativa fuzzy para livro
                $book = $bookRepo->createQueryBuilder('b')
                    ->where('b.name LIKE :name')
                    ->setParameter('name', '%' . $bibleBookName . '%')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                
                if ($book) {
                    $booksCache[$bibleBookName] = $book;
                } else {
                    $io->warning("Linha $count: Livro bíblico não encontrado: $bibleBookName");
                    $notFound++;
                    continue;
                }
            }

            // Buscar Obra
            if (!isset($worksCache[$workTitle])) {
                $work = $joannaWorkRepo->findOneBy(['title' => $workTitle]);
                // Se não achar, tenta limpar espaços extras ou invisíveis
                if (!$work) {
                     $work = $joannaWorkRepo->createQueryBuilder('w')
                        ->where('w.title LIKE :title')
                        ->setParameter('title', '%' . trim($workTitle) . '%') // Tenta match parcial
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                }
                $worksCache[$workTitle] = $work;
            }
            $work = $worksCache[$workTitle];

            if (!$work) {
                $io->warning("Linha $count: Obra não encontrada: $workTitle");
                $notFound++;
                continue;
            }

            // Converter tipo
            $refType = ReferenceType::fromString($typeStr);

            // Tentar localizar a referência
            // Estratégia 1: Busca Exata (incluindo verso final se existir)
            $qb = $referenceRepo->createQueryBuilder('r')
                ->where('r.work = :work')
                ->andWhere('r.bibleBook = :book')
                ->andWhere('r.bibleChapter = :bibleChapter')
                ->andWhere('r.bibleVerseStart = :verseStart')
                ->setParameter('work', $work)
                ->setParameter('book', $book)
                ->setParameter('bibleChapter', $bibleChapter)
                ->setParameter('verseStart', $bibleVerseStart);

            if ($bibleVerseEnd > 0) {
                 $qb->andWhere('r.bibleVerseEnd = :verseEnd')
                    ->setParameter('verseEnd', $bibleVerseEnd);
            }

            if ($refType !== ReferenceType::OUTRO) {
                $qb->andWhere('r.referenceType = :type')
                   ->setParameter('type', $refType);
            }

            $results = $qb->getQuery()->getResult();

            // Estratégia 2: Busca Relaxada (sem verso final ou tipo)
            if (count($results) === 0) {
                $qbRelaxed = $referenceRepo->createQueryBuilder('r')
                    ->where('r.work = :work')
                    ->andWhere('r.bibleBook = :book')
                    ->andWhere('r.bibleChapter = :bibleChapter')
                    ->andWhere('r.bibleVerseStart = :verseStart') // Pelo menos o início tem que bater
                    ->setParameter('work', $work)
                    ->setParameter('book', $book)
                    ->setParameter('bibleChapter', $bibleChapter)
                    ->setParameter('verseStart', $bibleVerseStart);
                
                $results = $qbRelaxed->getQuery()->getResult();
                
                if (count($results) > 0) {
                     $io->note("Linha $count: Encontrado via busca relaxada (sem verso final/tipo).");
                }
            }

            if (count($results) === 0) {
                // Tenta buscar sem o verso final estrito se não achou (ex: banco null e csv igual ao start)
                // Ou sem o tipo
                $io->warning("Linha $count: Referência não encontrada no banco. ($workTitle, $bibleBookName $bibleChapter:$bibleVerseStart)");
                $notFound++;
                continue;
            }

            // Se achou mais de um, tenta filtrar pelo capítulo de Joanna (string matching)
            $reference = $results[0]; // Pega o primeiro por padrão
            if (count($results) > 1) {
                foreach ($results as $res) {
                    if ($res->getJoannaChapter() === $joannaChapter) {
                        $reference = $res;
                        break;
                    }
                    // Tentar match parcial "6" em "6 - Título"
                    $chapterNum = (int) $joannaChapter;
                    if ((int)$res->getJoannaChapter() === $chapterNum) {
                        $reference = $res;
                        break;
                    }
                }
            }

            // Verificar se já aprovou
            if ($reference->hasUserApproved($user)) {
                // $io->note("Linha $count: Já aprovada por Denise.");
                $skipped++;
                continue;
            }

            // Verificar se Denise é a autora (não pode aprovar)
            if ($reference->getCreatedBy() === $user) {
                $io->warning("Linha $count: Denise é a autora desta referência, não pode aprovar.");
                $skipped++;
                continue;
            }

            // Criar aprovação
            $approval = new ReferenceApproval();
            $approval->setReference($reference);
            $approval->setApprovedBy($user);
            
            $this->entityManager->persist($approval);
            $created++;

            if ($count % 20 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Cuidado com o user e caches se der clear!
                // Recarregar user e refazer caches se der clear
                $user = $this->entityManager->getRepository(User::class)->find($userId);
                $worksCache = []; // Limpar cache para evitar detached entities
                $booksCache = [];
            }
        }

        $this->entityManager->flush();
        fclose($handle);

        $io->success("Processamento concluído!");
        $io->table(
            ['Total Lidos', 'Aprovações Criadas', 'Já Existiam/Ignoradas', 'Ref Não Encontrada'],
            [[$count, $created, $skipped, $notFound]]
        );

        return Command::SUCCESS;
    }
}
