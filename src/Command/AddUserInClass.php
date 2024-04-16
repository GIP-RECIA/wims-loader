<?php
namespace App\Command;

use App\Service\WimsFileObjectCreatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('app:add-user-in-class', 'Ajout d\'un utilisateur dans une classe')]
class AddUserInClass extends Command
{
    public function __construct(
        private WimsFileObjectCreatorService $wimsFileObjectCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('idEtab', InputArgument::REQUIRED,
            "Id de l'établissement");
        $this->addArgument('idClass', InputArgument::REQUIRED,
            "Id de la classe");
        $this->addArgument('uid', InputArgument::REQUIRED,
            "Id de l'utilisateur");

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = Yaml::parseFile('data.yaml');
        $idEtab = $input->getArgument("idEtab");
        $idClass = $input->getArgument("idClass");
        $uid = $input->getArgument("uid");
        $dataStudent = [];

        if ($uid !== null) {
            $users = $data['users'];
            $dataStudent = $users[$uid];
        }

        $io = new SymfonyStyle($input, $output);

        $io->title('Ajout de l\'utilisateur dans la class :');
        $this->wimsFileObjectCreator->addUserInClass($dataStudent, $idEtab, $idClass, $uid);
        //$io->text($this->wimsFileObjectCreator->createNewGroupementDeClasses());

        return Command::SUCCESS;
    }
}