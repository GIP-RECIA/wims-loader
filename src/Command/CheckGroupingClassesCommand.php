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

use App\Repository\GroupingClassesRepository;
use App\Service\WimsFileObjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:check-grouping-classes',
    description: "Vérifie que tous les établissements en base existent dans le système de fichiers.",
    hidden: false,
)]
class CheckGroupingClassesCommand extends Command
{
    public function __construct(
        private GroupingClassesRepository $groupingClassesRepo,
        private WimsFileObjectService $wimsFileObjectService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Permet de vérifier que tous les établissements en base existent dans le système de fichiers")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $error = false;

        $io->title('Vérification des établissements');

        $groupingClassesList = $this->groupingClassesRepo->findAll();
        $total = count($groupingClassesList);
        $missing = [];
        $existing = [];

        // Afficher la barre uniquement dans un terminal interactif
        $showProgress = $output->isDecorated();

        if ($showProgress) {
            $io->progressStart($total);
        }

        foreach ($groupingClassesList as $groupingClasses) {
            if ($showProgress) {
                $io->progressAdvance();
            }

            $idWims = $groupingClasses->getIdWims();

            if ($idWims === null) {
                $missing[] = [
                    'name' => $groupingClasses->getName(),
                    'siren' => $groupingClasses->getSiren(),
                    'uai' => $groupingClasses->getUai(),
                    'reason' => 'ID WIMS null en base'
                ];
                $error = true;
                continue;
            }

            if (!$this->wimsFileObjectService->isGroupingClassesExist($idWims)) {
                $missing[] = [
                    'name' => $groupingClasses->getName(),
                    'siren' => $groupingClasses->getSiren(),
                    'uai' => $groupingClasses->getUai(),
                    'id_wims' => $idWims,
                    'reason' => 'Dossier absent dans le système de fichiers'
                ];
                $error = true;
            } else {
                $existing[] = [
                    'name' => $groupingClasses->getName(),
                    'siren' => $groupingClasses->getSiren(),
                    'uai' => $groupingClasses->getUai(),
                    'id_wims' => $idWims,
                ];
            }
        }

        if ($showProgress) {
            $io->progressFinish();
        }

        if ($error) {
            $io->error('Des incohérences ont été détectées');
            $io->section('Établissements manquants ou incorrects');

            $rows = [];
            foreach ($missing as $item) {
                $rows[] = [
                    $item['name'],
                    $item['siren'],
                    $item['uai'],
                    $item['id_wims'] ?? 'N/A',
                    $item['reason'],
                ];
            }

            $io->table(
                ['Nom', 'SIREN', 'UAI', 'ID WIMS', 'Raison'],
                $rows
            );

            $io->note(sprintf('%d établissement(s) sur %d sont incorrects', count($missing), $total));

            return Command::FAILURE;
        }

        $io->success(sprintf('Tous les %d établissements sont cohérents', $total));

        return Command::SUCCESS;
    }
}
