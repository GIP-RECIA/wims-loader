<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:check-log',
    description: "Permet de tester que les logs fonctionnent bien.",
    hidden: true,
)]
class LogTesterCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Permet de tester que les logs fonctionnent bien")
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->text("Ajout d'un log d'info");
        $io->info("Message de log d'info");
        $this->logger->info("Message de log d'info");

        $io->text("Ajout d'un log de warning");
        $io->warning("Message de log de warning");
        $this->logger->warning("Message de log de warning");

        $io->text("Ajout d'un log d'erreur");
        $io->error("Message de log d'erreur");
        $this->logger->error("Message de log d'erreur");

        return Command::SUCCESS;
    }
}