<?php
// src/Command/CheckAllCommand.php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:check-all',
    description: 'Lance l\'ensemble des checks applicatifs (connexions, grouping classes, cohortes)',
)]
final class CheckAllCommand extends Command
{
    /**
     * Liste des commandes à exécuter, dans l'ordre.
     *
     * @var string[]
     */
    private const array SUB_COMMANDS = [
        'wims-loader:check-connections',
        'wims-loader:check-grouping-classes',
        'wims-loader:check-cohorts',
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();

        if ($application === null) {
            $io->error('Impossible de récupérer l\'application console.');

            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;

        foreach (self::SUB_COMMANDS as $commandName) {
            $io->section(sprintf('Exécution de "%s"', $commandName));

            try {
                $command = $application->find($commandName);
            } catch (\Symfony\Component\Console\Exception\CommandNotFoundException $e) {
                $io->error(sprintf('Commande "%s" introuvable : %s', $commandName, $e->getMessage()));
                $exitCode = Command::FAILURE;

                continue;
            }

            $subInput = new ArrayInput([]);
            $subInput->setInteractive(false);

            $returnCode = $command->run($subInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                $exitCode = Command::FAILURE;
                $io->warning(sprintf('"%s" a échoué (code %d).', $commandName, $returnCode));
            }
        }

        if ($exitCode === Command::SUCCESS) {
            $io->success('Tous les checks sont OK.');
        } else {
            $io->error('Au moins un check a échoué.');
        }

        return $exitCode;
    }
}