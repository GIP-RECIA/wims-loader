<?php
namespace App\Service;

use App\Exception\BuildIndexException;
use App\Exception\DirectoryAlreadyExistException;
use App\Exception\InvalidGroupingClassesException;
use App\Exception\InvalidUserException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class WimsFileObjectCreator
{

    public function __construct(
        private Environment $twig,
        private array $config,
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
            $id = strval(rand($this->config['grouping_classes_id']['min'],
            $this->config['grouping_classes_id']['max']));
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
        $finder = new Finder();
        $result = $finder->in($this->config['directory_structure'])
            ->directories()->name($this->config['grouping_classes_id']['regex']);
        $directories = [];

        foreach ($result as $directory) {
            $directories[] = $directory->getFileName();
        }

        return $directories;
    }

    /**
     * Permet de créer un nouveau groupement de classes
     *
     * @param array $dataSupervisor Les données du superviseur à insérer
     * @param array $dataGroupingClasses Les données du groupement de classes à insérer
     * @param string|null $id L'identifiant du groupement de classes. S'il n'est
     *  pas fournit, un nouveau sera généré automatiquement.
     *
     * @return string L'identifiant du groupement de classes créé
     */
    public function createNewGroupingClasses(
        array $dataSupervisor = [], array $dataGroupingClasses = [],
        string $id = null): string
    {
        $dataSupervisor = array_replace(
            $this->config['default_template_data']['supervisor'],
            $dataSupervisor
        );
        $dataGroupingClasses = array_replace(
            $this->config['default_template_data']['grouping_classes'],
            $dataGroupingClasses
        );

        if ($id === null) {
            $id = $this->generateStructureId();
        }

        $folder = $racine = $this->config['directory_structure'] . '/' . $id;
        $this->createStructure($folder, $dataSupervisor, $dataGroupingClasses);

        $output = exec($this->config['directory_structure'].'/.build-index');

        if ($output === false) {
            throw new BuildIndexException();
        }

        return $id;
    }

    /**
     * Permet de créer un prof dans un groupement de class
     *
     * @param array $dataTeacher Les données du professeur pour l'insertion
     * @param array $dataGroupingClasses Les données du groupement de classes
     *  pour l'insertion
     * @param string $id L'identifiant du groupement de classes dans lequel
     *  créer le prof
     */
    public function createTeacherInGroupingClasses(
        array $dataTeacher, array $dataGroupingClasses, string $id)
    {
        if (!$this->isGroupingClassesExist($id)) {
            throw new InvalidGroupingClassesException(
                "Le groupement de classes avec l'id '$id' n'existe pas"
            );
        }

        $dataTeacher = array_replace(
            $this->config['default_template_data']['supervisor'],
            $dataTeacher
        );
        $dataGroupingClasses = array_replace(
            $this->config['default_template_data']['grouping_classes'],
            $dataGroupingClasses
        );
        $uid = $dataTeacher['uid'];

        if ($this->isTeacherRegisteredInGroupingClasses($id, $uid)) {
            throw new InvalidUserException(
                "Le professeur '$uid' est déjà présent dans le groupement de " .
                "classes '$id'"
            );
        }

        // TODO: reprendre la suite de cette fonction
        $conf = $this->config['teacher'];
        $racine = $this->config['directory_structure'].'/'.$id;
        $templateData = ['teacher' => $dataTeacher];

        if (!$this->filesystem->exists($racine)) {
            throw new DirectoryNotFoundException($racine);
        }

        foreach ($conf['files_append'] as $fileName) {
            $file = $racine.'/'.$fileName;
            $template = $this->twig->load('teacher/'.$fileName.'.twig');
            $content = $template->render($templateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            file_put_contents($file, $contentWindows1252, FILE_APPEND);
            $this->filesystem->chmod($file, $this->config['file_right']);
        }

        foreach ($conf['files_create'] as $fileName) {
            $file = $racine.'/'.$fileName;
            $template = $this->twig->load('teacher/'.$fileName.'.twig');
            $content = $template->render($templateData);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            $file = str_replace("{uid}", $templateData['teacher']['uid'], $file);
            file_put_contents($file, $contentWindows1252, FILE_APPEND);
            $this->filesystem->chmod($file, $this->config['file_right']);
        }
    }

    /**
     * Créé une nouvelle classe dans le groupement de classes
     *
     * @param array $dataTeacher Les données du professeur à insérer
     * @param array $dataClass Les données de la classe à insérer
     * @param string $id L'identifiant du groupement de classes
     * @param string $uid L'identifiant du superviseur (professeur)
     * @return void
     */
    public function createClassInGroupingClasses(
        array $dataTeacher, array $dataClass, string $id, string $uid): void
    {
        // Contrôles sur le groupement de classes
        if (!$this->isGroupingClassesExist($id)) {
            throw new InvalidGroupingClassesException(
                "Le groupement de classes '$id' n'existe pas"
            );
        }

        if (!$this->isTeacherRegisteredInGroupingClasses($id, $uid)) {
            throw new InvalidUserException(
                "Le professeur '$uid' est absent de ce groupement de classes"
            );
        }

        $dataTeacher = array_replace(
            $this->config['default_template_data']['teacher'],
            $dataTeacher
        );
        $dataClass = array_replace(
            $this->config['default_template_data']['class'],
            $dataClass
        );
        $idClass = $this->findFirstAvailableIdForClass($id);
        $folder = $racine = $this->config['directory_structure'] . '/' . $id
            . '/' . $idClass;

        $this->createStructure($folder, $dataTeacher, $dataClass);

        // TODO: ajouter la modif du fichier id/.users/uid a la ligne user_supervise
    }

    /**
     * Permet de tester si un groupement de classes existe
     *
     * @param string $id L'identifiant du groupement de classes
     * @return boolean true s'il existe, false sinon
     *
     * @throws InvalidGroupingClassesException Si l'identifiant du
     *  groupement de classes est mal formé ou si la structure du groupement
     *  de classes est invalide
     */
    private function isGroupingClassesExist(string $id): bool
    {
        // Vérification de la structure de l'id
        if (!preg_match($this->config['grouping_classes_id']['regex'], $id)) {
            throw new InvalidGroupingClassesException(
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
            throw new InvalidGroupingClassesException(
                "'$folder' n'est pas un dossier de groupement de classes " .
                "mais un fichier"
            );
        }

        // Vérification des sous répertoires du groupement de classes
        foreach ($this->config['structure']['sub_folders'] as $subFolder) {
            $subFolder = $folder . '/' . $subFolder;

            if (!$this->filesystem->exists($subFolder)) {
                throw new InvalidGroupingClassesException(
                    "Le sous dossier '$subFolder' pour le groupement de " .
                    "classes '$id' n'existe pas"
                );
            }

            if (!is_dir($subFolder)) {
                throw new InvalidGroupingClassesException(
                    "'$subFolder' devrait être un dossier mais est un fichier"
                );
            }
        }

        // Vérification des fichiers du groupement de classes
        foreach ($this->config['structure']['files'] as $file) {
            $file = $folder . '/' . $file;

            if (!$this->filesystem->exists($file)) {
                throw new InvalidGroupingClassesException(
                    "Le fichier '$file' pour le groupement de " .
                    "classes '$id' n'existe pas"
                );
            }

            if (!is_file($file)) {
                throw new InvalidGroupingClassesException(
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
     * @throws InvalidGroupingClassesException S'il y'a un soucis avec le
     *  groupement de classes
     */
    private function isTeacherRegisteredInGroupingClasses(string $id, string $uid): bool
    {
        // Vérification de la structure de l'uid
        if (!preg_match($this->config['user_id']['regex'], $uid)) {
            throw new InvalidUserException(
                "Le format de l'id du user suivant : '$uid' est invalide"
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

    /**
     * Permet de créer une structure de type groupement de classes ou classe
     *
     * @param string $folder        Le répertoire de création de la structure
     * @param array $dataSupervisor Les données du superviseur
     * @param array $dataStructure  Les données de la structure
     * @return void
     */
    private function createStructure(
        string $folder, array $dataSupervisor, array $dataStructure): void
    {
        $conf = $this->config['structure'];
        $data = [
            'supervisor' => $dataSupervisor,
            'structure' => $dataStructure
        ];
        
        if ($this->filesystem->exists($folder)) {
            throw new DirectoryAlreadyExistException(
                "Le répertoire de structure '$folder' existe déjà"
            );
        }

        $this->filesystem->mkdir($folder, $this->config['directory_right']);

        foreach ($conf['sub_folders'] as $subFolder) {
            $this->filesystem->mkdir($folder.'/'.$subFolder,
                $this->config['directory_right']);
        }

        foreach ($conf['files'] as $fileName) {
            $file = $folder.'/'.$fileName;
            $template = $this->twig->load('structure/'.$fileName.'.twig');
            $content = $template->render($data);
            $contentWindows1252 = iconv('UTF-8', 'WINDOWS-1252', $content);
            file_put_contents($file, $contentWindows1252);
            $this->filesystem->chmod($file, $this->config['file_right']);
        }
    }
}
