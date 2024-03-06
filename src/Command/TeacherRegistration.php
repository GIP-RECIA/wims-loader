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
use Symfony\Component\Yaml\Yaml;

#[AsCommand('app:teacher-registration', 'Inscription du prof dans l\'établissement')]
class TeacherRegistration extends Command
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
            "User id");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = Yaml::parseFile('data.yaml');
        $etablissementId = $input->getArgument("etablissementId");
        $uid = $input->getArgument("uid");
        $dataTeacher = [];

        if ($uid !== null) {
            $users = $data['users'];
            $dataTeacher = $users[$uid];
        }

        $dataGroupingClasses = [];
        $io = new SymfonyStyle($input, $output);

        $io->title('Inscription du prof :');
        $this->wimsFileObjectCreator->createTeacherInGroupingClasses(
            $dataTeacher, $dataGroupingClasses, $etablissementId);

        return Command::SUCCESS;
    }
}