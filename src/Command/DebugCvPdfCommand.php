<?php

namespace App\Command;

use App\Repository\CvRepository;
use App\Service\CvPdfGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:debug-cv-pdf',
    description: 'Generates the CV PDF for a given CV id and writes debug files under var/.',
)]
final class DebugCvPdfCommand extends Command
{
    public function __construct(
        private readonly CvRepository $cvRepository,
        private readonly CvPdfGenerator $cvPdfGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('cvId', InputArgument::REQUIRED, 'CV id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cvId = (int) $input->getArgument('cvId');
        $cv = $this->cvRepository->find($cvId);
        if (!$cv) {
            $output->writeln('<error>CV not found.</error>');
            return Command::FAILURE;
        }

        $response = $this->cvPdfGenerator->downloadResponse($cv, true);
        $content = $response->getContent() ?? '';

        $varDir = \dirname(__DIR__, 2).'/var';
        if (!is_dir($varDir)) {
            @mkdir($varDir, 0775, true);
        }

        $path = $varDir.'/debug-cv-'.$cvId.'.pdf';
        file_put_contents($path, $content);

        $output->writeln('Wrote: '.$path);
        $output->writeln('Bytes: '.strlen($content));
        $output->writeln('HTML: '.$varDir.'/last-cv-pdf.html');

        return Command::SUCCESS;
    }
}

