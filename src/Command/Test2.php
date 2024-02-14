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

#[AsCommand('app:test2', 'test des commandes')]
class Test2 extends Command
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

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $etablissementId = $input->getArgument("etablissementId");
        $data = [];
        $io = new SymfonyStyle($input, $output);

        $io->title('Inscription du prof :');
        $this->wimsFileObjectCreator->createTeacherInGroupementDeClasses($data, $etablissementId);
        //$io->text($this->wimsFileObjectCreator->createNewGroupementDeClasses());

        return Command::SUCCESS;
    }
}