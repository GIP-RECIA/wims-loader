<?php
namespace App\Command;

use App\Service\WimsFileObjectCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('app:create-class', 'création de classe')]
class CreateClass extends Command
{
    public function __construct(
        private WimsFileObjectCreator $wimsFileObjectCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('etablissementId', InputArgument::REQUIRED,
            "Id de l'établissement");
        $this->addArgument('uid', InputArgument::REQUIRED,
            "Id de l'utilisateur");
        $this->addOption('className', null, InputOption::VALUE_REQUIRED,
        "Nom de la classe");

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = Yaml::parseFile('data.yaml');
        $etablissementId = $input->getArgument("etablissementId");
        $uid = $input->getArgument("uid");
        $className = $input->getOption("className");
        $dataTeacher = [];
        $dataClass = [];

        if ($uid !== null) {
            $users = $data['users'];
            $dataTeacher = $users[$uid];
        }

        if ($className) {
            $dataClass['description'] = $className;
        }

        $io = new SymfonyStyle($input, $output);

        $io->title('Création de la class :');
        $this->wimsFileObjectCreator->createClassInGroupingClasses($dataTeacher, $dataClass, $etablissementId, $uid);
        //$io->text($this->wimsFileObjectCreator->createNewGroupementDeClasses());

        return Command::SUCCESS;
    }
}