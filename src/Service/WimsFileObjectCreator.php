<?php
namespace App\Service;

use App\Exception\InvalidGroupementDeClassesException;
use App\Exception\InvalidUserException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class WimsFileObjectCreator
{
    // Le format d'un identifiant de groupement de classes
    private const REGEX_GROUPEMENT_DE_CLASSES_ID = '/^[1-9]\d{6}$/';

    // Le format d'un identifiant d'un user id'
    private const REGEX_USER_ID = '/^[a-z0-9]{8}$/';

    // La liste de tous les sous-répertoires d'un groupement de classes ou d'une
    //  classe
    private const STRUCTURE_SUB_FOLDERS = [
        'cdt', 'def', 'doc', 'exams', 'freeworks', 'freeworksdata', 'livret',
        'noscore', 'score', 'seq', 'sheets', 'src', 'tool', '.users', 'vote'
    ];
    // La liste de tous les fichiers d'un groupement de classes ou d'une classe
    private const STRUCTURE_FILES = [
        '.def', 'Exindex', 'Extitles', 'supervisor', '.userlist', 'version'
    ];
    //private const 

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
     * @param array $data Les données pour l'insertion, si non fournis on
     *  prends les valeurs par défaut du fichier de config
     * @param string|null $id L'identifiant du groupement de classes. S'il n'est
     *  pas fournit, un nouveau sera généré automatiquement.
     *
     * @return string L'identifiant du groupement de classes créé
     */
    public function createNewGroupementDeClasses(array $data = [], string $id = null): string
    {
        $conf = $this->config['groupement_de_classes'];
        $defaultTemplateData = $this->config['default_template_data'];
        $templateData = array_replace_recursive($defaultTemplateData, $data);

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
            $content = $template->render($templateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            file_put_contents($file, $contentWindows1252);
            $this->filesystem->chmod($file, 0644);
        }

        $output = shell_exec($this->config['directory_structure'].'/.build-index');

        /*if ($output !== null) {
            echo "Commande exécutée avec succès. Sortie : $output";
        } else {
            echo "Erreur lors de l'exécution de la commande.";
        }*/

        return $id;
    }

    /**
     * Permet de créer un prof dans un groupement de class
     *
     * @param array $data Les données pour l'insertion, si non fournis on
     *  prends les valeurs par défaut du fichier de config
     * @param string $id L'identifiant du groupement de classes dans lequel
     *  créer le prof
     */
    public function createTeacherInGroupementDeClasses(array $data = [], string $id)
    {
        // TODO: Contrôler l'existence du groupement de class
        // TODO: avant tout traitement vérifier l'existence des fichiers et de l'utilisateur
        //  Si utilisateur déjà présent déclencher une exception
        $conf = $this->config['teacher'];
        $defaultTemplateData = $this->config['default_template_data'];
        $templateData = array_replace_recursive($defaultTemplateData, $data);
        $racine = $this->config['directory_structure'].'/'.$id;

        if (!$this->filesystem->exists($racine)) {
            throw new DirectoryNotFoundException($racine);
        }

        foreach ($conf['files_append'] as $fileName) {
            $file = $racine.'/'.$fileName;
            $template = $this->twig->load('teacher/'.$fileName.'.twig');
            $content = $template->render($templateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            file_put_contents($file, $contentWindows1252, FILE_APPEND);
            $this->filesystem->chmod($file, 0644);
        }

        foreach ($conf['files_create'] as $fileName) {
            $file = $racine.'/'.$fileName;
            $template = $this->twig->load('teacher/'.$fileName.'.twig');
            $content = $template->render($templateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            $file = str_replace("{uid}", $templateData['teacher']['uid'], $file);
            file_put_contents($file, $contentWindows1252, FILE_APPEND);
            $this->filesystem->chmod($file, 0644);
        }

        //$output = shell_exec($this->config['directory_structure'].'/.build-index');

        /*if ($output !== null) {
            echo "Commande exécutée avec succès. Sortie : $output";
        } else {
            echo "Erreur lors de l'exécution de la commande.";
        }*/
    }

    public function createClassInGroupementDeClasses(array $data = [], string $id, string $uid)
    {
        if (!$this->isTeacherRegisteredInGroupementDeClasses($id, $uid)) {
            throw new InvalidUserException(
                "Le professeur '$uid' est absent de ce groupement de classes"
            );
        }

        $idClass = $this->findFirstAvailableIdForClass($id);

    }

    /**
     * Permet de tester si un groupement de classes existe
     *
     * @param string $id L'identifiant du groupement de classes
     * @return boolean true s'il existe, false sinon
     *
     * @throws InvalidGroupementDeClassesException Si l'identifiant du
     *  groupement de classes est mal formé ou si la structure du groupement
     *  de classes est invalide
     */
    private function isGroupementDeClassesExist(string $id): bool
    {
        // Vérification de la structure de l'id
        if (!preg_match($this::REGEX_GROUPEMENT_DE_CLASSES_ID, $id)) {
            throw new InvalidGroupementDeClassesException(
                "Le format de l'id du groupement de classes suivant : '$id' " .
                "est invalide, il devrait être constitué de 7 chiffres"
            );
        }

        $folder = $this->config['directory_structure'].'/'.$id;

        // Vérification de l'existence du dossier du groupement de classes
        if (!$this->filesystem->exists($folder)) {
            return false;
        }

        if (!is_dir($folder)) {
            throw new InvalidGroupementDeClassesException(
                "'$folder' n'est pas un dossier de groupement de classes " .
                "mais un fichier"
            );
        }

        // Vérification des sous répertoires du groupement de classes
        foreach ($this::STRUCTURE_SUB_FOLDERS as $subFolder) {
            $subFolder = $folder . '/' . $subFolder;

            if (!$this->filesystem->exists($subFolder)) {
                throw new InvalidGroupementDeClassesException(
                    "Le sous dossier '$subFolder' pour le groupement de " .
                    "classes '$id' n'existe pas"
                );
            }

            if (!is_dir($subFolder)) {
                throw new InvalidGroupementDeClassesException(
                    "'$subFolder' devrait être un dossier mais est un fichier"
                );
            }
        }

        // Vérification des fichiers du groupement de classes
        foreach ($this::STRUCTURE_FILES as $file) {
            $file = $folder . '/' . $file;

            if (!$this->filesystem->exists($file)) {
                throw new InvalidGroupementDeClassesException(
                    "Le fichier '$file' pour le groupement de " .
                    "classes '$id' n'existe pas"
                );
            }

            if (!is_file($file)) {
                throw new InvalidGroupementDeClassesException(
                    "'$file' devrait être un fichier mais est un dossier"
                );
            }
        }

        return true;
    }

    /**
     * Permet de tester si un groupement de classes existe et si le professeur
     * existe dedans
     *
     * @param string $id L'identifiant du groupement de classes
     * @param string $uid L'identifiant de du professeur
     * @return boolean true s'il existe, false sinon
     *
     * @throws InvalidGroupementDeClassesException S'il y'a un soucis avec le
     *  groupement de classes
     */
    private function isTeacherRegisteredInGroupementDeClasses(string $id, string $uid): bool
    {
        // Contrôles sur le groupement de classes
        if (!$this->isGroupementDeClassesExist($id)) {
            throw new InvalidGroupementDeClassesException(
                "Le groupement de classes '$id' n'existe pas"
            );
        }

        // Vérification de la structure de l'uid
        if (!preg_match($this::REGEX_USER_ID, $uid)) {
            throw new InvalidUserException(
                "Le format de l'id du user suivant : '$id' est invalide"
            );
        }

        $folder = $this->config['directory_structure'].'/'.$id;

        // On commence par vérifier le fichier .teacherlist
        $regex = '/^[^,]*,[^,]*,' . $uid . '$/m';

        if (!preg_match($regex, file_get_contents($folder . "/.teacherlist"))) {
            return false;
        }
        
        // Puis le fichier .teacherlist_external
        $regex = '/^' . $uid . ':' . $uid . '$/m';

        if (!preg_match($regex, file_get_contents($folder . "/.teacherlist_external"))) {
            return false;
        }

        return true;
    }

    /**
     * Permet de trouver le premier id disponible pour une classe dans un
     * groupement de classes
     *
     * @param string $id L'identifiant du groupement de classes
     * @return string L'identifiant disponible pour la classe
     */
    private function findFirstAvailableIdForClass(string $id): string
    {
        $folder = $this->config['directory_structure'].'/'.$id;
        $finder = new Finder();
        $finder->directories()->name('/^\d+$/');
        $ids = [];

        foreach ($finder->in($folder) as $dir) {
            $ids[] = intval($dir->getRelativePathname());
        }

        $idClass = 1;

        while (in_array($idClass, $ids)) {
            $idClass++;
        }
        
        return strval($idClass);
    }
}
