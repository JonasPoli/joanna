<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:db:dump',
    description: 'Dumps the MySQL database to a file.',
)]
class DbDumpCommand extends Command
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
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'The file to dump to', 'sql/backup.sql')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getOption('file');
        
        // Ensure directory exists
        $directory = dirname($file);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                $output->writeln(sprintf('<error>Failed to create directory "%s".</error>', $directory));
                return Command::FAILURE;
            }
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
        $checkProcess = Process::fromShellCommandline('which mysqldump');
        $checkProcess->run();
        if (!$checkProcess->isSuccessful()) {
            $output->writeln('<error>Error: "mysqldump" is not installed or not in your PATH.</error>');
            $output->writeln('<comment>Please install MySQL client tools. On macOS: brew install mysql-client</comment>');
            return Command::FAILURE;
        }

        // Build command with password inline to avoid interactive prompt issues, 
        // referencing MYSQL_PWD env var is safer than command line args but for simplicity we use straight command
        // Note: mysqldump -u... -pPassword (no space)
        
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s',
            $host,
            $port,
            $user,
            $pass ? '-p' . escapeshellarg($pass) : '',
            $dbname,
            $file
        );
        
        // Use env var for password to avoid warnings/ps showing it
        $env = [];
        if ($pass) {
            $env['MYSQL_PWD'] = $pass;
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s %s > %s',
                $host,
                $port,
                $user,
                $dbname,
                $file
            );
        }

        $output->writeln(sprintf("Dumping MySQL database '%s' to '%s'...", $dbname, $file));

        $process = Process::fromShellCommandline($command, null, $env);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Error dumping database:</error>');
            $output->writeln($process->getErrorOutput());
            return Command::FAILURE;
        }

        $output->writeln('<info>Database dump created successfully.</info>');

        return Command::SUCCESS;
    }
}
