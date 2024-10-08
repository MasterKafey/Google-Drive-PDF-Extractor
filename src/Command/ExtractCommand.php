<?php

namespace App\Command;

use App\Business\GoogleDrive;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:extract')]
class ExtractCommand extends Command
{
    public function __construct(
        private readonly GoogleDrive $googleDrive,
        private readonly string $extractingFolder,
    )
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addArgument('folder-id', InputArgument::REQUIRED, 'Folder ID');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $folderId = $input->getArgument('folder-id');
        $zipPath = $this->extractingFolder . '\\' . date('Y-m-d_H-i-s') . '.zip';

        try {
            $this->googleDrive->downloadFolderAsZip($folderId, $zipPath);
            $output->writeln("Le fichier ZIP a été créé : " . $zipPath);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        return Command::SUCCESS;
    }
}