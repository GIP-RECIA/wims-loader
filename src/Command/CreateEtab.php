<?php
namespace App\Command;

use App\Service\WimsFileObjectCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('app:create-etab', 'création d\'établissement')]
class CreateEtab extends Command
{
    public function __construct(
        private WimsFileObjectCreator $wimsFileObjectCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('etabName', null, InputOption::VALUE_REQUIRED,
            "Nom de l'établissement");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = Yaml::parseFile('data.yaml');

        // Récupérer les données de test
        //$users = $data['users'];

        $io = new SymfonyStyle($input, $output);

        $etabName = $input->getOption("etabName");
        $dataGroupingClasses = [];

        if ($etabName) {
            $dataGroupingClasses['institution_name'] = $etabName;
        }

        $io->title('Création du groupement de classes :');
        $io->text($this->wimsFileObjectCreator->createNewGroupingClasses(
            [], $dataGroupingClasses));

        return Command::SUCCESS;
    }
}