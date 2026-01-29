<?php

namespace App\Command;

use App\Entity\Bible\BibleVersion;
use App\Entity\Bible\Book;
use App\Entity\Bible\Testament;
use App\Entity\Bible\Verse;
use App\Entity\Bible\VerseReference;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-bible',
    description: 'Imports Bible data from legacy database',
)]
class ImportBibleCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        // #[Autowire('%env(LEGACY_DATABASE_URL)%')]
        // private string $legacyDatabaseUrl
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Importing Bible Data from Legacy Database');
        
        // Explicitly defining parameters to avoid URL parsing issues or empty ENV
        $connectionParams = [
            'dbname' => 'biblia-tradutor',
            'user' => 'root',
            'password' => 'wab12345678',
            'host' => '127.0.0.1',
            'port' => 3306,
            'driver' => 'pdo_mysql',
        ];
        $legacyConn = DriverManager::getConnection($connectionParams);

        // setSQLLogger is deprecated in newer DBAL versions and removed in DBAL 4.
        // Logging is typically handled by middleware configuration in Symfony.
        // We will skip explicit disabling here.

        // 1. Import Versions
        $io->section('Importing Bible Versions');
        $versions = $legacyConn->fetchAllAssociative('SELECT * FROM bible_version');
        foreach ($versions as $row) {
            if (!isset($row['version']) && $io->isVeryVerbose()) {
                 $io->note('Columns found: ' . implode(', ', array_keys($row)));
            }
            // Fallback if 'version' key is missing, maybe it's 'name'?
            $versionName = $row['version'] ?? $row['name'] ?? $row['title'] ?? null;
            
            if (!$versionName) {
                 print_r(array_keys($row)); // Force print to see what we have
                 die('Cannot find version name column');
            }

            $version = $this->entityManager->getRepository(BibleVersion::class)->findOneBy(['version' => $versionName]);
            if (!$version) {
                $version = new BibleVersion();
                $version->setVersion($versionName);
            }
            // Map other fields if they exist in legacy, assuming standard names or adjusting
            if (isset($row['info'])) $version->setInfo($row['info']);
            if (isset($row['copyright'])) $version->setCopyright($row['copyright']);
            
            $this->entityManager->persist($version);
        }
        $this->entityManager->flush();
        $io->success(count($versions) . ' versions imported.');

        // 2. Import Testaments
        $io->section('Importing Testaments');
        $testaments = $legacyConn->fetchAllAssociative('SELECT * FROM testament');
        foreach ($testaments as $row) {
             // Checking by ID or Name. ID is safer if we want to keep relationships
            $testament = $this->entityManager->getRepository(Testament::class)->find($row['id']);
             if (!$testament) {
                // If we want to preserve IDs, we might need to disable auto-increment or force it
                // Doctrine doesn't easily allow setting ID on auto-increment strategy.
                // For simplicity, we search by name or create new.
                // Ideally, we should truncate mappings or use metadata set IdGeneratorType to NONE
                
                 $testament = new Testament();
                 $testament->setName($row['name']);
            }
            $this->entityManager->persist($testament);
        }
        $this->entityManager->flush();
         // Re-fetch to build map for Book import
        $testamentMap = []; 
        // Need to refetch with newly assigned IDs or map by name if name is unique
        foreach ($this->entityManager->getRepository(Testament::class)->findAll() as $t) {
            $testamentMap[$t->getName()] = $t;
        }
        $io->success(count($testaments) . ' testaments imported.');


        // 3. Import Books
        $io->section('Importing Books');
        $books = $legacyConn->fetchAllAssociative('SELECT * FROM book');
        $bookMap = []; // old_id => new_entity
        
        // We need mapping from old testament ID to new testament entity.
        // Assuming legacy 'testament' table has 'id', 'name'.
        // And 'book' has 'testament_id'.
        
        // Let's cache old testaments to map them
        $legacyTestaments = [];
        foreach($testaments as $t) {
            $legacyTestaments[$t['id']] = $t['name'];
        }

        foreach ($books as $row) {
            $book = $this->entityManager->getRepository(Book::class)->findOneBy(['name' => $row['name']]);
            if (!$book) {
                $book = new Book();
                $book->setName($row['name']);
            }
            if (isset($row['abbrev'])) $book->setAbbrev($row['abbrev']);
            
            // Link Testament
            if (isset($row['testament_id']) && isset($legacyTestaments[$row['testament_id']])) {
                $tName = $legacyTestaments[$row['testament_id']];
                if (isset($testamentMap[$tName])) {
                    $book->setTestament($testamentMap[$tName]);
                }
            }
            
            $this->entityManager->persist($book);
             // We won't flush every row, but maybe every batch?
             // Need to keep track of this book for Verses
        }
        $this->entityManager->flush();
        
        // Re-build book map for Verses
        // Using Name as key might be risky if different testaments have same book names (unlikely).
        $newBooks = $this->entityManager->getRepository(Book::class)->findAll();
        $bookNameMap = [];
        foreach($newBooks as $b) {
            $bookNameMap[$b->getName()] = $b;
        }

        // We need to map Legacy Book ID to New Book Entity
        // So we iterate legacy books again or cache them earlier
        $legacyBookIdNameMap = [];
        foreach($books as $b) {
            $legacyBookIdNameMap[$b['id']] = $b['name'];
        }

        $io->success(count($books) . ' books imported.');

        // 4. Import Verses
        $io->section('Importing Verses (This may take a while)');
        
        // Query in batches or use a big iterator
        $offset = 0;
        $limit = 1000;
        
        // For performance, get Version entity
        // Assuming only one version for now or mapping legacy 'version_id'
        // Let's assume the legacy 'verse' table has 'version_id' which maps to 'bible_version.id'
        
        // Build Version Map
        $legacyVersions = [];
        foreach($versions as $v) {
            $vName = $v['version'] ?? $v['name'] ?? $v['title'] ?? 'Unknown';
            $legacyVersions[$v['id']] = $vName;
        }
        $newVersionsMap = [];
        foreach($this->entityManager->getRepository(BibleVersion::class)->findAll() as $v) {
            $newVersionsMap[$v->getVersion()] = $v;
        }

        while (true) {
            $sql = "SELECT * FROM verse LIMIT $limit OFFSET $offset";
            $verses = $legacyConn->fetchAllAssociative($sql);
            if (empty($verses)) {
                break;
            }

            foreach ($verses as $row) {
                if (!isset($row['text']) && $offset == 0) {
                     $io->note('Verse Columns found: ' . implode(', ', array_keys($row)));
                }
                $text = $row['text'] ?? $row['verse_text'] ?? $row['content'] ?? '';
                
                 // To avoid duplicate checks on massive table, we assume empty target DB for verses or use insert ignore
                // Here standard entity persist
                
                $verse = new Verse();
                $verse->setChapter($row['chapter']);
                $verse->setVerse($row['verse']);
                $verse->setText($text);
                
                // Map Book
                if (isset($legacyBookIdNameMap[$row['book_id']])) {
                    $bName = $legacyBookIdNameMap[$row['book_id']];
                    if (isset($bookNameMap[$bName])) {
                        $val = $bookNameMap[$bName];
                        $bookRef = is_object($val) ? $val : $this->entityManager->getReference(Book::class, $val);
                        $verse->setBook($bookRef);
                    }
                }
                
                // Map Version
                 if (isset($row['version_id']) && isset($legacyVersions[$row['version_id']])) {
                    $vName = $legacyVersions[$row['version_id']];
                    if (isset($newVersionsMap[$vName])) {
                        $val = $newVersionsMap[$vName];
                        $verRef = is_object($val) ? $val : $this->entityManager->getReference(BibleVersion::class, $val);
                        $verse->setVersion($verRef);
                    }
                } elseif (!empty($newVersionsMap)) {
                     // Fallback to first version
                     $val = reset($newVersionsMap);
                     $verRef = is_object($val) ? $val : $this->entityManager->getReference(BibleVersion::class, $val);
                     $verse->setVersion($verRef);
                }

                $this->entityManager->persist($verse);
            }
            $this->entityManager->flush();
            $this->entityManager->clear(); // Clear identity map to free memory
            
            // Re-merge needed entities for next batch if we were reusing them, 
            // but we are fetching them from repositories inside the loop or using cached arrays.
            // Entities in $bookNameMap are detached after clear(). We need to reload them or simply reference by ID (getReference)
            // Using getReference is better for performance.
            
            // Updating maps to use IDs for getReference
            if ($offset == 0) {
                 // Optimization: Convert object maps to ID maps
                 // bookNameMap -> bookName => ID
                 // newVersionsMap -> versionName => ID
                 foreach($bookNameMap as $k => $v) $bookNameMap[$k] = $v->getId();
                 foreach($newVersionsMap as $k => $v) $newVersionsMap[$k] = $v->getId();
            }
            
            // Wait, previous iteration loop used objects. We need to refactor slightly for optimization in next loop
            // or just reload. 
            // Correct approach with clear(): use references.
            
             $offset += $limit;
             $io->text("Processed $offset verses...");
        }
        
        $io->success('Verses imported.');

        return Command::SUCCESS;
    }
}
