<?php

namespace App\Command;

use App\Entity\Bible\BibleVersion;
use App\Entity\Bible\Book;
use App\Entity\Bible\Testament;
use App\Entity\Bible\Verse;
use App\Entity\Joanna\JoannaReference;
use App\Entity\Joanna\JoannaWork;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:export-data',
    description: 'Exports all tables to CSV files',
)]
class ExportDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        $exportDir = $this->projectDir . '/var/export/' . date('Y-m-d_H-i-s');
        $filesystem->mkdir($exportDir);

        $io->title("Exporting data to $exportDir");

        $entities = [
            'users' => User::class,
            'joanna_works' => JoannaWork::class,
            'joanna_references' => JoannaReference::class,
            'bible_versions' => BibleVersion::class,
            'bible_testaments' => Testament::class,
            'bible_books' => Book::class,
            'bible_verses' => Verse::class,
        ];

        foreach ($entities as $name => $class) {
            $io->text("Exporting $name...");
            $this->exportEntity($class, "$exportDir/$name.csv");
        }

        $io->success("Export completed successfully.");

        return Command::SUCCESS;
    }

    private function exportEntity(string $class, string $filePath): void
    {
        $repository = $this->entityManager->getRepository($class);
        $query = $repository->createQueryBuilder('e')->getQuery();
        
        $fp = fopen($filePath, 'w');
        
        $headerWritten = false;
        $iterableResult = $query->toIterable();

        foreach ($iterableResult as $entity) {
            // Simple reflection to get properties/getters? 
            // Or just specific serialization?
            // For now, let's use a simple array cast or specific getters if we want to be strict.
            // Using a generic way:
            $data = $this->extractData($entity);
            
            if (!$headerWritten) {
                fputcsv($fp, array_keys($data));
                $headerWritten = true;
            }
            fputcsv($fp, $data);
            
            $this->entityManager->detach($entity);
        }

        fclose($fp);
    }

    private function extractData(object $entity): array
    {
        // Customizable extraction logic
        $data = [];
        $reflection = new \ReflectionClass($entity);
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value)) {
                if (method_exists($value, 'getId')) {
                    $value = $value->getId(); // ID reference
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } elseif ($value instanceof \UnitEnum) { // Handle Enums
                    $value = $value->value ?? $value->name;
                } else {
                    $value = 'Object(' . (new \ReflectionClass($value))->getShortName() . ')';
                }
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }
            
            $data[$property->getName()] = $value;
        }

        return $data;
    }
}
