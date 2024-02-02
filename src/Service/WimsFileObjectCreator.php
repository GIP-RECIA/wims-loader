<?php
namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class WimsFileObjectCreator
{
    const MIN_STRUCTURE_ID = 1000000;
    const MAX_STRUCTURE_ID = 9999999;
    const REGEX_STRUCTURE_ID = '/[0-9]{7}/';

    public function __construct(
        private Environment $twig,
        private array $config,
        private Finder      $finder     = new Finder(),
        private Filesystem  $filesystem = new Filesystem()
    ) {
    }

    /**
     * Génère un nouvel id pour une structure en contrôlant qu'il n'existe pas
     * encore sur le système de fichier
     *
     * @return string
     */
    public function generateStructureId(): string
    {
        $directories = $this->listAllStructureWithoutSample();

        do {
            $id = strval(rand($this->config['structure_id']['min'],
            $this->config['structure_id']['max']));
        } while (in_array($id, $directories));

        return $id;
    }

    /**
     * Liste les différentes structures qui ne sont pas des exemples
     *
     * @return string[] La liste des structures
     */
    public function listAllStructureWithoutSample(): array
    {
        $result = $this->finder->in($this->config['directory_structure'])
            ->directories()->name($this->config['structure_id']['regex']);
        $directories = [];

        foreach ($result as $directory) {
            $directories[] = $directory->getFileName();
        }

        return $directories;
    }

    /**
     * Permet de créer un nouveau groupement de classes
     *
     * @param string|null $id L'identifiant du groupement de classes. S'il n'est
     *  pas fournit, un nouveau sera généré automatiquement.
     *
     * @return string L'identifiant du groupement de classes créé
     */
    public function createNewGroupementDeClasses(string $id = null): string
    {
        $conf = $this->config['groupement_de_classes'];
        $defaultTemplateData = $this->config['default_template_data'];

        if ($id === null) {
            $id = $this->generateStructureId();
        }

        $racine = $this->config['directory_structure'].'/'.$id;
        $this->filesystem->mkdir($racine, $this->config['directory_right']);

        foreach ($conf['directories'] as $directory) {
            $this->filesystem->mkdir($racine.'/'.$directory,
                $this->config['directory_right']);
        }

        foreach ($conf['files'] as $fileName) {
            $file = $racine.'/'.$fileName;
            $template = $this->twig->load('groupementDeClasses/'.$fileName.'.twig');
            $content = $template->render($defaultTemplateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            file_put_contents($file, $contentWindows1252);
            $this->filesystem->chmod($file, 0644);
        }

        $this->filesystem->chown($racine, $this->config['user'], true);
        $this->filesystem->chgrp($racine, $this->config['group'], true);

        return $id;
    }
}