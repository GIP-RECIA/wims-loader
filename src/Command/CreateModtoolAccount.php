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

use App\Service\LdapService;
use App\Service\WimsFileObjectService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'wims-loader:create-modtool-account',
    description: "Permet d'ajouter un compte Modtool à partir de l'uid de l'utilisateur.",
    hidden: false,
)]
class CreateModtoolAccount extends Command
{
    public function __construct(
        private LdapService $ldapService,
        private WimsFileObjectService $wimsFileObjectService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription("Permet d'ajouter un compte Modtool à partir de l'uid ou le mail de l'utilisateur.")
            ->addArgument('uidOrMail', InputArgument::REQUIRED, "L'uid ou le mail de l'utilisateur")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uidOrMail = $input->getArgument('uidOrMail');
        $userData = null;

        if (strpos($uidOrMail, '@') !== false) {
            $userData = $this->ldapService->findOneUserByMail($uidOrMail);
        } else {
            $userData = $this->ldapService->findOneUserByUid($uidOrMail);
        }

        $login = $userData->getAttribute('ENTPersonLogin')[0];
        $password = $this->generatePassword();
        $mail = $userData->getAttribute('mail')[0];

        $this->wimsFileObjectService->createModtoolAccount(
            $userData->getAttribute('uid')[0],
            $login,
            $password,
            $userData->getAttribute('givenName')[0],
            $userData->getAttribute('sn')[0],
            $mail
        );

        $io->text("login : " . $login);
        $io->text("password : " . $password);
        $io->text("mail : " . $mail);

        return Command::SUCCESS;
    }

    private function generatePassword(int $length = 10): string
    {
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $all = $upper . $lower . $digits;

        // Garantit au moins un caractère de chaque catégorie
        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        for ($i = count($password); $i < $length; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
}
