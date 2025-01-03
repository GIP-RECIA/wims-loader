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

use App\Repository\CohortRepository;
use App\Service\CohortService;
use App\Service\WimsFileObjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'wims-loader:check-cohort',
    description: "Contrôle la cohérence d'une cohorte.",
    hidden: false,
)]
class CheckCohortCommand extends Command
{
    public function __construct(
        private CohortService $cohortService,
        private CohortRepository $cohortRepo,
        private WimsFileObjectService $wimsFileObjectService,
        private Filesystem  $filesystem = new Filesystem(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Permet de contrôler qu'une cohorte est cohérente")
            ->addArgument('cohort-id', InputArgument::REQUIRED, "L'identifiant de la cohort que l'on souhaite contrôler au format XXXXXXX/X.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fullIdCohort = $input->getArgument('cohort-id');
        $error = false;

        if (!$this->cohortService->isFullIdConsistent($fullIdCohort)) {
            $io->error("Le format de l'identifiant de la cohorte est invalide");

            return Command::FAILURE;
        }

        $io->title('Contrôle de cohérence de la cohorte ' . $fullIdCohort);

        //$io->section("Nombre d'élèves");

        $cohort = $this->cohortRepo->findCohortByFullIdWims($fullIdCohort);

        if (null === $cohort) {
            $io->error("La cohorte n'existe pas côté wims-loader");
            $error = true;
        }

        $folderCohort = $this->wimsFileObjectService->getRootFolder() . "/" . $fullIdCohort;
        $fileUserList = $folderCohort . "/.userlist";

        if (!file_exists($fileUserList)) {
            $io->error("La cohorte n'existe pas côté wims");
            $error = true;
        }

        if ($error) {
            return Command::FAILURE;
        }

        $nbStudentsInWimsLoader = count($cohort->getStudents());
        $lignes = file($fileUserList, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
        $nbStudentsInWims = count($lignes);

        if ($nbStudentsInWimsLoader !== $nbStudentsInWims) {
            $io->error("Le nombre d'élèves est différent entre wims et wims-loader :");
            $io->error(" - wims-loader : " . $nbStudentsInWimsLoader);
            $io->error(" - wims        : " . $nbStudentsInWims);

            return Command::FAILURE;
        }

        $io->writeln("Le nombre d'élève est bien de $nbStudentsInWims dans wims et wims-loader");


        return Command::SUCCESS;
    }
}