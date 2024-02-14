<?php
namespace App\Command;

use App\Service\WimsFileObjectCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand('app:test3', 'test des commandes')]
class Test3 extends Command
{
    public function __construct(
        private WimsFileObjectCreator $wimsFileObjectCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('etablissementId', InputArgument::REQUIRED,
            "Nom de l'établissement");
        $this->addArgument('uid', InputArgument::REQUIRED,
            "Id de l'utilisateur");

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $etablissementId = $input->getArgument("etablissementId");
        $uid = $input->getArgument("uid");
        $data = [];
        $io = new SymfonyStyle($input, $output);

        $io->title('Création de la class :');
        $this->wimsFileObjectCreator->createClassInGroupementDeClasses($data, $etablissementId, $uid);
        //$io->text($this->wimsFileObjectCreator->createNewGroupementDeClasses());

        return Command::SUCCESS;
    }
}