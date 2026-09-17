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
use App\Repository\GroupingClassesRepository;
use App\Service\WimsFileObjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:check-cohorts',
    description: "Vérifie que toutes les classes et groupes pédagogiques en base existent dans le système de fichiers.",
    hidden: false,
)]
class CheckCohortsCommand extends Command
{
    public function __construct(
        private GroupingClassesRepository $groupingClassesRepo,
        private CohortRepository $cohortRepo,
        private WimsFileObjectService $wimsFileObjectService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Permet de vérifie que toutes les classes et groupes pédagogiques en base existent dans le système de fichiers.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $error = false;

        $io->title('Vérification des classes et groupes pédagogiques');

        $cohortsList = $this->cohortRepo->findAllData();
        $total = count($cohortsList);
        $missing = [];
        $existing = [];

        $io->progressStart($total);

        foreach ($cohortsList as $cohort) {
            $io->progressAdvance();

            $idWims = $cohort['id_wims'];

            if ($idWims === null) {
                $missing[] = [
                    'name' => $cohort['c_name'],
                    'etab' => $cohort['gc_name'],
                    'uai' => $cohort['uai'],
                    'reason' => 'ID WIMS null en base'
                ];
                $error = true;
                continue;
            }

            if (!$this->wimsFileObjectService->isClassExist($cohort['groupingClasses_id_wims'], $cohort['cohort_id_wims'])) {
                $missing[] = [
                    'name' => $cohort['c_name'],
                    'etab' => $cohort['gc_name'],
                    'uai' => $cohort['uai'],
                    'id_wims' => $idWims,
                    'reason' => 'Dossier absent dans le système de fichiers'
                ];
                $error = true;
            } else {
                $existing[] = [
                    'name' => $cohort['c_name'],
                    'etab' => $cohort['gc_name'],
                    'uai' => $cohort['uai'],
                    'id_wims' => $idWims,
                ];
            }
        }

        $io->progressFinish();

        if ($error) {
            $io->error('Des incohérences ont été détectées');
            $io->section('Établissements manquants ou incorrects');

            $rows = [];
            foreach ($missing as $item) {
                $rows[] = [
                    $item['name'],
                    $item['etab'],
                    $item['uai'],
                    $item['id_wims'] ?? 'N/A',
                    $item['reason'],
                ];
            }

            $io->table(
                ['Nom', 'etab', 'UAI', 'ID WIMS', 'Raison'],
                $rows
            );

            $io->note(sprintf('%d établissement(s) sur %d sont incorrects', count($missing), $total));

            return Command::FAILURE;
        }

        $io->success(sprintf('Tous les %d établissements sont cohérents', $total));

        return Command::SUCCESS;
    }
}
