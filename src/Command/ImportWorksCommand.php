<?php

namespace App\Command;

use App\Entity\Joanna\JoannaWork;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-works',
    description: 'Imports Joanna Works from CSV',
)]
class ImportWorksCommand extends Command
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
        $csvPath = $this->projectDir . '/docs/obras.csv';

        if (!file_exists($csvPath)) {
            $io->error("File not found: $csvPath");
            return Command::FAILURE;
        }

        $io->title("Importing works from $csvPath");

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ","); // Skip header: ano,Obra

            $count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // ano,Obra
                // CSV lines might be: 1997,Adolescência e Vida
                
                $year = isset($data[0]) ? (int)$data[0] : null;
                $title = isset($data[1]) ? trim($data[1]) : null;

                if (!$title) continue;

                $work = $this->entityManager->getRepository(JoannaWork::class)->findOneBy(['title' => $title]);
                if (!$work) {
                    $work = new JoannaWork();
                    $work->setTitle($title);
                }
                
                if ($year) {
                    $work->setPublicationYear($year);
                }

                $this->entityManager->persist($work);
                $count++;
            }
            fclose($handle);

            $this->entityManager->flush();
            $io->success("$count works imported/updated.");
        }

        return Command::SUCCESS;
    }
}
