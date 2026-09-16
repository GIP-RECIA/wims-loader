<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'wims-loader:new-year',
    description: 'Réinitialise l\'application pour une nouvelle année scolaire.'
)]
class NewYearCommand extends Command
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $question = new ConfirmationQuestion(
            'Ceci va complètement effacer les données afin de repartir sur une nouvelle année scolaire.' . PHP_EOL .
            'Êtes-vous sûr de vouloir poursuivre ?',
            false
        );

        if (!$io->askQuestion($question)) {
            $io->warning('Opération annulée.');

            return Command::SUCCESS;
        }

        $application = $this->getApplication();

        if (!$application) {
            return Command::FAILURE;
        }

        $io->section('Suppression du schéma');

        $application->find('doctrine:schema:drop')->run(
            new ArrayInput(['--force' => true]),
            $output
        );

        $io->section('Création du schéma');

        $application->find('doctrine:schema:create')->run(
            new ArrayInput([]),
            $output
        );

        $io->success('La base de données a été réinitialisée.');

        $io->section('Mise à jour de la date d\'expiration des classes');

        $this->updateExpirationDate();

        return Command::SUCCESS;
    }

    private function updateExpirationDate(): void
    {
        $year = (int) date('Y') + 1;
        $expirationDate = sprintf('%d0815', $year);

        $envFile = $this->kernel->getProjectDir() . '/.env.local';

        $content = file_exists($envFile)
            ? file_get_contents($envFile)
            : '';

        $newContent = preg_replace(
            '/^CLASSES_EXPIRATION_DATE=.*$/m',
            'CLASSES_EXPIRATION_DATE="' . $expirationDate . '"',
            $content,
            -1,
            $count
        );

        if ($count === 0) {
            $newContent .= PHP_EOL . 'CLASSES_EXPIRATION_DATE="' . $expirationDate . '"' . PHP_EOL;
        }

        file_put_contents($envFile, $newContent);
    }
}
