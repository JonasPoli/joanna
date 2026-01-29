<?php

namespace App\Command;

use App\Entity\Bible\Book;
use App\Entity\Joanna\JoannaReference;
use App\Entity\Joanna\JoannaWork;
use App\Entity\User;
use App\Enum\ReferenceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-references',
    description: 'Imports Joanna References from CSV',
)]
class ImportReferencesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvPath = $this->projectDir . '/docs/referencias.csv';

        if (!file_exists($csvPath)) {
            $io->error("File not found: $csvPath");
            return Command::FAILURE;
        }

        // Get Admin User (created earlier or we create it now? The user said "cadastre o dev com admin").
        // We will do user creation in a separate step or here if needed.
        // Check for 'admin' (login requested) or 'admin@example.com'
        $admin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin']); 
        if (!$admin) {
             // Let's create a default admin for import attribution
             // Or fail? I'll creating a dummy system user or use the dev one from the request later.
             // Request: "cadastre o dev com login admin e a senha wab12345678"
             // I'll skip user attribution for now or fetch by specific email if I create it.
             // I will implement "Create Admin" later. For now, reference 'createdBy' is nullable in my entity?
             // Checking Entity... I set `nullable: false`. So I MUST have a user.
             // I will create the admin user here if not exists.
             $admin = new User();
             $admin->setEmail('admin'); // Login admin
             $admin->setRoles(['ROLE_DEV']);
             $admin->setName('System Admin');
             // Password needs hashing.
             $hashedPassword = $this->passwordHasher->hashPassword(
                $admin,
                'wab12345678'
             );
             $admin->setPassword($hashedPassword);
             $this->entityManager->persist($admin);
             $this->entityManager->flush();
        }

        // Cache Books and Works
        $books = [];
        foreach($this->entityManager->getRepository(Book::class)->findAll() as $b) {
            $books[mb_strtolower($b->getName())] = $b;
            // Also map abbreviations or variations if needed
        }
        
        $works = [];
        foreach($this->entityManager->getRepository(JoannaWork::class)->findAll() as $w) {
            // Mapping by exact title for now
            $works[mb_strtolower($w->getTitle())] = $w;
        }

        $io->title("Importing references from $csvPath");

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            // Header: livro,Capítulo,Verso inicial,Verso final,Obra,Capítulo,Tipo de Referência,Citação
            $header = fgetcsv($handle, 2000, ","); 

            $count = 0;
            $batchSize = 200;
            $i = 0;

            while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
                // Index mapping:
                // 0: Livro (Bíblia)
                // 1: Cap (Bíblia)
                // 2: Verso Inicial
                // 3: Verso Final
                // 4: Obra (Joanna)
                // 5: Cap (Joanna)
                // 6: Tipo
                // 7: Citação

                $bookName = isset($data[0]) ? trim($data[0]) : null;
                if (!$bookName) continue;

                $bibleChapter = isset($data[1]) ? (int)$data[1] : null;
                $verseStart = isset($data[2]) ? (int)$data[2] : null;
                $verseEnd = isset($data[3]) && is_numeric($data[3]) ? (int)$data[3] : $verseStart; // Handle '-' or empty

                $workTitle = isset($data[4]) ? trim($data[4]) : null;
                $joannaChapter = isset($data[5]) ? trim($data[5]) : null;
                $typeStr = isset($data[6]) ? trim($data[6]) : '';
                $citation = isset($data[7]) ? trim($data[7]) : '';

                // Resolve Book
                $bookEntity = $books[mb_strtolower($bookName)] ?? null;
                if (!$bookEntity) {
                    // Try without accents or fuzzy?
                    // For now, log warning and skip
                    // $io->warning("Book not found: $bookName");
                    continue; 
                }

                // Resolve Work
                $workEntity = $works[mb_strtolower($workTitle)] ?? null;
                if (!$workEntity) {
                    // Start auto-creating works if missing? Or skip?
                    // Given we ran ImportWorks first, they should exist.
                    // But CSV might have slight diffs.
                    // $io->warning("Work not found: $workTitle");
                     // Let's create it on the fly to be robust
                    $workEntity = new JoannaWork();
                    $workEntity->setTitle($workTitle);
                    $this->entityManager->persist($workEntity);
                    $works[mb_strtolower($workTitle)] = $workEntity;
                }

                $reference = new JoannaReference();
                $reference->setBibleBook($bookEntity);
                $reference->setBibleChapter($bibleChapter);
                $reference->setBibleVerseStart($verseStart);
                $reference->setBibleVerseEnd($verseEnd);
                
                $reference->setWork($workEntity);
                $reference->setJoannaChapter($joannaChapter);
                
                $reference->setReferenceType(ReferenceType::fromString($typeStr));
                $reference->setCitation($citation);
                
                $reference->setCreatedBy($admin);

                $this->entityManager->persist($reference);
                
                $i++;
                if (($i % $batchSize) === 0) {
                    $this->entityManager->flush();
                    // $this->entityManager->clear(); // Avoid detached entities issues with cached arrays
                }
                $count++;
            }
            fclose($handle);

            $this->entityManager->flush();
            $io->success("$count references imported.");
        }

        return Command::SUCCESS;
    }
}
