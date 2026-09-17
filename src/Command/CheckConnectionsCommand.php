<?php
// src/Command/CheckConnectionsCommand.php

namespace App\Command;

use App\Service\LdapService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:check-connections',
    description: 'Vérifie la connexion à la base de données et au LDAP',
)]
final class CheckConnectionsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[\SensitiveParameter]
        private readonly array $ldapConfig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hasError = false;

        // --- Check BDD ---
        $io->section('Connexion base de données');
        try {
            $this->connection->executeQuery('SELECT 1');
            $params = $this->connection->getParams();
            $io->success(sprintf(
                'Connexion BDD OK (%s@%s:%s/%s)',
                $params['user'] ?? '?',
                $params['host'] ?? '?',
                $params['port'] ?? '?',
                $params['dbname'] ?? '?',
            ));
        } catch (DbalException $e) {
            $hasError = true;
            $io->error(sprintf('Connexion BDD KO : %s', $e->getMessage()));
        }

        // --- Check LDAP ---
        $io->section('Connexion LDAP');
        try {
            // Le bind se fait dans le constructeur de LdapService :
            // si ça échoue, l'exception est levée ici, dans ce try/catch.
            new LdapService($this->ldapConfig);
            $io->success(sprintf(
                'Connexion LDAP OK (bind réussi sur %s:%s)',
                $this->ldapConfig['host'] ?? '?',
                $this->ldapConfig['port'] ?? '?',
            ));
        } catch (\Exception $e) {
            $hasError = true;
            $io->error(sprintf('Connexion LDAP KO : %s', $e->getMessage()));
        }

        if ($hasError) {
            $io->error('Au moins une connexion a échoué.');

            return Command::FAILURE;
        }

        $io->success('Toutes les connexions sont opérationnelles.');

        return Command::SUCCESS;
    }
}