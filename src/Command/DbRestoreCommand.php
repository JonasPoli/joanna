<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:db:restore',
    description: 'Restores the MySQL database from a file.',
)]
class DbRestoreCommand extends Command
{
    private string $databaseUrl;

    public function __construct(string $databaseUrl)
    {
        $this->databaseUrl = $databaseUrl;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'The SQL file to restore from')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        
        if (!file_exists($file)) {
            $output->writeln(sprintf('<error>File "%s" not found.</error>', $file));
            return Command::FAILURE;
        }

        // Parse DATABASE_URL
        $urlComponents = parse_url($this->databaseUrl);

        if (!$urlComponents) {
            $output->writeln('<error>Invalid DATABASE_URL.</error>');
            return Command::FAILURE;
        }
        
        $scheme = $urlComponents['scheme'] ?? '';
        if ($scheme !== 'mysql') {
            $output->writeln('<error>Only MySQL is supported by this command.</error>');
            return Command::FAILURE;
        }

        $user = $urlComponents['user'] ?? '';
        $pass = $urlComponents['pass'] ?? '';
        $host = $urlComponents['host'] ?? '127.0.0.1';
        $port = $urlComponents['port'] ?? '3306';
        $dbname = ltrim($urlComponents['path'] ?? '', '/');

        // Check availability
        $checkProcess = Process::fromShellCommandline('which mysql');
        $checkProcess->run();
        if (!$checkProcess->isSuccessful()) {
            $output->writeln('<error>Error: "mysql" is not installed or not in your PATH.</error>');
            $output->writeln('<comment>Please install MySQL client tools. On macOS: brew install mysql-client</comment>');
            return Command::FAILURE;
        }

        // Use env var for password for security
        $env = [];
        
        // Use sed to replace utf8mb4_0900_ai_ci (MySQL 8.0) with utf8mb4_unicode_ci (MariaDB/Older MySQL)
        // This fixes compatibility issues when restoring dumps from newer MySQL versions to MariaDB
        $sedCommand = sprintf('sed "s/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g" %s', escapeshellarg($file));
        
        $mysqlCommand = sprintf(
            'mysql -h %s -P %s -u %s %s',
            $host,
            $port,
            $user,
            $dbname
        );

        if ($pass) {
            $env['MYSQL_PWD'] = $pass;
        }

        $command = sprintf('%s | %s', $sedCommand, $mysqlCommand);

        $output->writeln(sprintf("Restoring MySQL database '%s' from '%s' (with collation fix)...", $dbname, $file));

        $process = Process::fromShellCommandline($command, null, $env);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Error restoring database:</error>');
            $output->writeln($process->getErrorOutput());
            return Command::FAILURE;
        }

        $output->writeln('<info>Database restored successfully.</info>');

        return Command::SUCCESS;
    }
}
