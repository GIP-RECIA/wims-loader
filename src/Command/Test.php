<?php
namespace App\Command;

use App\Service\WimsFileObjectCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand('app:test', 'test des commandes')]
class Test extends Command
{
    public function __construct(
        private WimsFileObjectCreator $wimsFileObjectCreator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $directories = $this->wimsFileObjectCreator->listAllStructureWithoutSample();

        $io->title('Les résultats');

        foreach ($directories as $directory) {
            $io->text($directory);
        }

        $io->title('Des nouveaux id');

        for ($i = 0; $i < 5; $i++) {
            $io->text($this->wimsFileObjectCreator->generateStructureId());
        }

        $io->title('Création du groupement de classes :');
        $io->text($this->wimsFileObjectCreator->createNewGroupementDeClasses());

        return Command::SUCCESS;
    }
}