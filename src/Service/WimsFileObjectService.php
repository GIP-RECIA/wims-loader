<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\Service;

use App\Entity\Cohort;
use App\Entity\GroupingClasses;
use App\Entity\User;
use App\Exception\BuildIndexException;
use App\Exception\DirectoryAlreadyExistException;
use App\Exception\InvalidClassException;
use App\Exception\InvalidGroupingClassesException;
use App\Exception\InvalidUserException;
use Exception;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class WimsFileObjectService
{

    public function __construct(
        private Environment $twig,
        private array $config,
        private Filesystem  $filesystem = new Filesystem()
    ) {
    }

    /**
     * Permet de récupérer le répertoire racine du projet
     *
     * @return string La racine
     */
    public function getRootFolder(): string
    {
       return $this->config['directory_structure'];
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
        $result = $finder->in($this->getRootFolder())
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
     *
     * @return string L'identifiant du groupement de classes créé
     */
    public function createNewGroupingClasses(
        array $dataSupervisor = [], array $dataGroupingClasses = []): string
    {
        $dataSupervisor = array_replace(
            $this->config['default_template_data']['supervisor'],
            $dataSupervisor
        );
        $dataGroupingClasses = array_replace(
            $this->config['default_template_data']['grouping_classes'],
            $dataGroupingClasses
        );
        $id = $this->generateStructureId();

        $folder = $this->getRootFolder() . '/' . $id;
        $this->createStructure($folder, $dataSupervisor, $dataGroupingClasses);

        $output = exec($this->getRootFolder() . '/.build-index');

        if ($output === false) {
            throw new BuildIndexException();
        }

        return $id;
    }

    /**
     * Permet de créer un nouveau groupement de classes
     *
     * @param GroupingClasses $groupingClasses Le groupement de classes à insérer
     *
     * @return GroupingClasses Le groupement de classes mit à jour
     */
    public function createNewGroupingClassesFromObj(GroupingClasses $groupingClasses): GroupingClasses
    {
        $idWims = $this->createNewGroupingClasses(
            [],
            $this->fromObjGroupingClassesToDataArray($groupingClasses)
        );

        return $groupingClasses->setIdWims($idWims);
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
            $this->config['default_template_data']['teacher'],
            $dataTeacher
        );
        $dataGroupingClasses = array_replace(
            $this->config['default_template_data']['grouping_classes'],
            $dataGroupingClasses
        );
        $uid = $dataTeacher['uid'];
        $this->testUserIdFormat($uid);

        if ($this->isTeacherRegisteredInGroupingClasses($id, $uid)) {
            throw new InvalidUserException(
                "Le professeur '$uid' est déjà présent dans le groupement de " .
                "classes '$id'"
            );
        }

        $this->fileProcessing(
            $this->config['teacher'],
            $this->getRootFolder() . '/' . $id,
            $dataTeacher,
            'teacher'
        );
    }
    
    /**
     * Permet de créer un prof dans un groupement de class
     *
     * @param User $teacher L'enseignant pour l'insertion
     * @param GroupingClasses $groupingClasses Le groupement de classes pour l'insertion
     */
    public function createTeacherInGroupingClassesFromObj(User $teacher, GroupingClasses $groupingClasses): void
    {
        $this->createTeacherInGroupingClasses(
            $this->fromObjUserToDataArray($teacher),
            $this->fromObjGroupingClassesToDataArray($groupingClasses),
            $groupingClasses->getIdWims());
    }

    /**
     * Créé une nouvelle classe dans le groupement de classes
     *
     * @param array $dataTeacher Les données du professeur à insérer
     * @param array $dataClass Les données de la classe à insérer
     * @param string $id L'identifiant du groupement de classes
     * @param string $uid L'identifiant du superviseur (professeur)
     * @return string L'identifiant de la classe
     */
    public function createClassInGroupingClasses(
        array $dataTeacher, array $dataClass, string $id, string $uid): string
    {
        $this->testGroupingClassesIdFormat($id);
        $this->testUserIdFormat($uid);

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

        $idClass = $this->findFirstAvailableIdForClass($id);
        $folderGroupingClasses = $this->getRootFolder() . '/' . $id;
        $folderClass = $folderGroupingClasses . '/' . $idClass;

        $dataTeacher = array_replace(
            $this->config['default_template_data']['teacher'],
            $dataTeacher
        );
        $dataClass = array_replace(
            $this->config['default_template_data']['class'],
            $dataClass
        );
        $dataClass['super_class'] = $dataClass['parent'] = $id;
        $dataClass['id_class'] = $idClass;
        $classInstitution = $this->getValueOfLine(
            $folderGroupingClasses . '/.def', '!set class_institution');
        $dataClass['institution_name'] = $classInstitution;
        $className = $dataClass['description'];

        if (!$this->isValidClassName($className)) {
            throw new InvalidClassException(
                "Le nom de la classe suivant : '$className' est invalide"
            );
        }

        $this->createStructure($folderClass, $dataTeacher, $dataClass);
        $subClassFile = $folderGroupingClasses . '/.subclasses';

        if (!$this->filesystem->exists($subClassFile)) {
            $this->filesystem->touch($subClassFile);
            $this->filesystem->chmod($subClassFile, $this->config['file_right']);
        }

        $data = ['structure' => $dataClass, 'user' => $dataTeacher];
        $content = $this->renderInWindows1252('other/.subclasses.twig', $data);
        $this->filesystem->appendToFile($subClassFile, $content);
        $fileUsersUid = $folderGroupingClasses . '/.users' . '/' . $uid;
        $userSupervise = $this->getValueOfLine($fileUsersUid, '!set user_supervise');

        if ($userSupervise === null) {
            // Ici on ajoute la ligne qui n'existent pas
            $content = $this->renderInWindows1252('other/uid-sup.twig', ['user' => [
                'supervise' => $id . '/' . $idClass
            ]]);
            $this->filesystem->appendToFile($fileUsersUid, $content);
        } else {
            // Ici on modifie seulement la ligne en question
            $content = self::Windows1252ToUtf8(file_get_contents($fileUsersUid));
            $content = preg_replace('/^(!set user_supervise=.*)$/m', '$1,' . $id . '/' . $idClass, $content);
            file_put_contents($fileUsersUid, self::utf8ToWindows1252($content));
        }

        return $idClass;
    }

    /**
     * Créé une nouvelle cohorte wims dans le groupement de classes
     *
     * @param Cohort $cohort La cohorte wims a créer
     * @return Cohort La cohorte mise à jour avec les données wims
     */
    public function createCohortInGroupingClassesFromObj(Cohort $cohort): Cohort
    {
        $idWims = $this->createClassInGroupingClasses(
            $this->fromObjUserToDataArray($cohort->getTeacher()),
            $this->fromObjCohortToDataArray($cohort),
            $cohort->getGroupingClasses()->getIdWims(),
            $cohort->getTeacher()->getUid(),
        );
        return $cohort->setIdWims($idWims);
    }

    /**
     * Permet d'insérer un élève dans une classe d'un groupement de classes
     *
     * @param array $dataUser   Les données de l'élève
     * @param string $id        L'identifiant du groupement de classe
     * @param string $idClass   L'identifiant de la classe
     * @return void
     */
    public function addUserInClass(array $dataUser, string $id, string $idClass): void
    {
        $dataUser = array_replace(
            $this->config['default_template_data']['student'],
            $dataUser
        );
        $dataUser['participate'] = $id . '/' . $idClass;
        $uid = $dataUser['uid'];
        $groupingClassesFolder = $this->getRootFolder() . '/' . $id;
        $classFolder = $groupingClassesFolder . '/' . $idClass;

        // Vérification sur les format des id
        $this->testGroupingClassesIdFormat($id);
        $this->testClassIdFormat($idClass);
        $this->testUserIdFormat($uid);

        // Vérification de l'existence des structures
        $this->testGroupingClassesExist($id);
        $this->testClasseExist($id, $idClass);

        if (!$this->isUserRegisteredInGroupingClasses($id, $uid)) {
            // Insertion de l'élève au niveau du groupement de classes
            $this->fileProcessing(
                $this->config['grouping_classes_user'],
                $groupingClassesFolder,
                $dataUser,
                'user'
            );
        } else {
            if ($this->isUserRegisteredInClass($id, $idClass, $uid)) {
                throw new InvalidUserException(
                    "L'élève '$uid' est déjà présent dans la classe '$idClass'" .
                    " du groupement de classes '$id'"
                );
            }

            // Mise à jour de l'élève au niveau du groupement de classes
            $fileUsersUid = $groupingClassesFolder . '/.users' . '/' . $uid;
            $content = self::Windows1252ToUtf8(file_get_contents($fileUsersUid));
            $content = preg_replace('/^(!set user_participate=.*)$/m', '$1,' . $id . '/' . $idClass, $content);
            file_put_contents($fileUsersUid, self::utf8ToWindows1252($content));
        }

        // Insertion de l'élève au niveau de la classe
        $this->fileProcessing(
            $this->config['class_user'],
            $classFolder,
            $dataUser,
            'user'
        );

        // La suite des traitements ne concerne que les fichiers .usernextlist et .userprevlist
        $lines = file($classFolder . '/.userlist', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $data = [];

        foreach ($lines as $line) {
            $line = ltrim(self::Windows1252ToUtf8($line), ':');
            $elems = explode(',', $line);
            $data[$elems[2]] = ['nom' => $elems[0], 'prenom' => $elems[1]];
        }

        uasort($data, array('self', 'comparerNomPrenom'));

        $dataRes = [];
        $previous = null;
        $first = null;

        foreach ($data as $key => $value) {
            if ($previous != null) {
                $dataRes[] = $previous . ':' . $key;
            } else {
                $first = $key;
            }

            $previous = $key;
        }

        $dataRes[] = $previous . ':' . $first;
        $userNextList = '';
        $userPrevList = '';

        foreach ($dataRes as $value) {
            $userNextList .= $value . "\n";
            $userPrevList = $value . "\n" . $userPrevList;
        }

        file_put_contents($classFolder . '/.usernextlist', $userNextList);
        file_put_contents($classFolder . '/.userprevlist', $userPrevList);
    }

    /**
     * Permet d'insérer un élève dans une cohorte d'un groupement de classes
     *
     * @param User    $student L'étudiant a inscrire
     * @param Cohort $cohort   La cohorte dans laquelle inscrire l'étudiant
     * @return void
     */
    public function addUserInClassFromObj(User $student, Cohort $cohort): void
    {
        $this->addUserInClass(
            $this->fromObjUserToDataArray($student),
            $cohort->getGroupingClasses()->getIdWims(),
            $cohort->getIdWims()
        );
    }

    /**
     * Permet de récupérer une liste d'élèves à partir du fichier .userlist de
     * la cohorte dans wims
     * 
     * @param Cohort $cohort La cohorte dont on souhaite récupérer les élèves
     * @return string[][] La liste des élèves avec uid, nom, prénom
     */
    public function readUsersInClass(Cohort $cohort): array
    {
        $res = [];
        $fileName = $this->getRootFolder() . '/' . $cohort->getFullIdWims() . '/.userlist';

        if (!$this->filesystem->exists($fileName)) {
            throw new \Exception("Le fichier '$fileName' n'existe pas.");
        }

        $fileContents = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($fileContents as $line) {
            $line = ltrim(self::Windows1252ToUtf8($line), ':');
            $arr = explode(',', $line);
            $res[$arr[2]] = ['firstName' => $arr[1], 'lastName' => $arr[0]];
        }

        return $res;
    }

    /**
     * Permet de remplacer le prénom et le nom d'un élève dans le fichier
     * .userlist d'un établissement
     *
     * @param string $idWims L'identifiant wims de l'établissement/la classe
     * @param string $uid L'uid de l'élève
     * @param string $firstName Le prénom de l'élève
     * @param string $lastName Le nom de l'élève
     * @return void
     */
    public function replaceFirstNameAndLastNameInUserList(string $idWims, string $uid, string $firstName, string $lastName): void
    {
        $this->replaceFirstNameAndLastNameInFileList(
            $idWims . '/.userlist',
            $uid,
            $firstName,
            $lastName,
            ':'
        );
    }

    /**
     * Permet de remplacer le prénom et le nom d'un enseignant dans le fichier
     * .teacherlist d'un établissement
     *
     * @param string $idWims L'identifiant wims de l'établissement
     * @param string $uid L'uid de l'enseignant
     * @param string $firstName Le prénom de l'enseignant
     * @param string $lastName Le nom de l'enseignant
     * @return void
     */
    public function replaceFirstNameAndLastNameInTeacherList(string $idWims, string $uid, string $firstName, string $lastName): void
    {
        $this->replaceFirstNameAndLastNameInFileList(
            $idWims . '/.teacherlist',
            $uid,
            $firstName,
            $lastName
        );
    }

    /**
     * Permet de remplacer le prénom, le nom et le mail de l'utilisateur dans le
     * fichier spécifié
     *
     * @param string $fileName Le fichier a traiter
     * @param string $firstName Le prénom de l'utilisateur
     * @param string $lastName Le nom de l'utilisateur
     * @param string $mail Le mail de l'utilisateur
     * @return void
     */
    public function replaceDataInUidFile(string $fileName, string $firstName, string $lastName, string $mail): void
    {
        $fileName = $this->getRootFolder() . '/' . $fileName;

        if (!$this->filesystem->exists($fileName)) {
            throw new \Exception("Le fichier '$fileName' n'existe pas.");
        }

        $fileContents = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $searchLastName = '!set user_lastname=';
        $searchFirstName = '!set user_firstname=';
        $searchMail = '!set user_email=';

        // Parcourir chaque ligne et remplacer la ligne cible
        foreach ($fileContents as $key => $line) {
            $line = self::Windows1252ToUtf8($line);

            if (str_starts_with($line, $searchLastName)) {
                $fileContents[$key] = self::utf8ToWindows1252($searchLastName . $lastName);
            } else if (str_starts_with($line, $searchFirstName)) {
                $fileContents[$key] = self::utf8ToWindows1252($searchFirstName . $firstName);
            } else if (str_starts_with($line, $searchMail)) {
                $fileContents[$key] = self::utf8ToWindows1252($searchMail . $mail);
            }
        }

        $this->filesystem->dumpFile($fileName, implode(PHP_EOL, $fileContents) . PHP_EOL);
    }

    /**
     * Permet de récupérer la liste des id wims des cohortes de l'élève dans l'établissement
     *
     * @param GroupingClasses $groupingClasses L'établissement
     * @param User $student L'élève
     * @return string[] La liste des id wims
     */
    public function getIdCohortsOfStudentInGroupingClasses(GroupingClasses $groupingClasses, User $student): array
    {
        $filePath = $this->getRootFolder() . '/' . $groupingClasses->getIdWims() . '/.users/' . $student->getUid();
        $startFullId = $groupingClasses->getIdWims() . '/';

        // L'élève n'est actuellement inscrit dans aucune cohorte
        if (!$this->filesystem->exists($filePath)) {
            return [];
        }

        $handle = fopen($filePath, 'r');

        if ($handle) {
            // Lecture du fichier de l'utilisateur ligne par ligne
            while (($line = fgets($handle)) != false) {
                // Si on est sur la ligne qui liste les cohortes de l'élève, on les liste et les retourne
                if (str_starts_with($line, "!set user_participate=")) {
                    fclose($handle);

                    $fullIds = explode(',', explode('=', $line)[1]);
                    $res = [];

                    foreach ($fullIds as $fullId) {
                        if (str_starts_with($fullId, $startFullId)) {
                            $res[] = trim(explode('/', $fullId)[1]);
                        }
                    }

                    return $res;
                }
            }

            fclose($handle);
        } else {
            throw new Exception("Impossible d'ouvrir le fichier " . $filePath);
        }

        return [];
    }

    /**
     * Génère un compte Modtool
     * @param string $uid
     * @param string $login
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @param string $mail
     * @return void
     */
    public function createModtoolAccount(string $uid, string $login, string $password, string $firstName, string $lastName, string $mail): void
    {
        $filepath = $this->getRootFolder() . '/../.developers';
        $lines = [
            "# " . $uid . " - " . date('d/m/Y'),
            ":" . $login,
            $password,
            $firstName . "," . $lastName,
            $mail,
        ];

        $content = "";

        foreach ($lines as $line) {
            $content .= self::utf8ToWindows1252($line) . "\n";
        }

        file_put_contents($filepath, $content , FILE_APPEND);
    }

    /**
     * Récupère la liste des idWims d'établissement d'un utilisateur
     * @param \App\Entity\User $user
     * @return array
     */
    public function findGroupingClassesIdWimsOfUser(User $user): array
    {
        $res = [];
        $finder = new Finder();

        $finder->files()
            ->in($this->getRootFolder() . "/*/.users/")
            ->name($user->getUid())
            ->depth('== 0');

        foreach ($finder as $file) {
            $res[] = explode('/', $file->getPath())[5];
        }

        return $res;
    }


    /**
     * Efface tous les utilisateurs de la cohorte.
     * 
     * @param Cohort $cohort La cohorte que l'on souhaite vider
     * @return void
     */
    public function emptyClasses(Cohort $cohort): void
    {
        $usersInfo = $this->readUsersInClass($cohort);

        foreach ($usersInfo as $uid => $userInfo) {
            $filePath = $this->getRootFolder() . '/' . $cohort->getGroupingClasses()->getIdWims() . '/.users/' . $uid;
            $content = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $prefixToFind = '!set user_participate=';  // La ligne commence par !set user_participate=
    
            $newContent = [];
            $cid = $cohort->getFullIdWims();
            foreach ($content as $ligne) {
                if (str_starts_with($ligne, $prefixToFind)) {
                    // Suppression de la mention "7414903/2," ou "7414903/2" sans virgule
                    $ligne = str_replace([',' . $cid, $cid . ',', $cid], '', $ligne);
                }
    
                $newContent[] = $ligne;
            }
    
            // Écriture du contenu modifié dans le fichier
            file_put_contents($filePath, implode(PHP_EOL, $newContent));
        }

        $files = ['.userlist', '.userlist_external', '.usernextlist', '.userprevlist'];

        foreach ($files as $file) {
            file_put_contents($this->getRootFolder() . '/' . $cohort->getFullIdWims() . '/' . $file, '');
        }
    }

    /**************************************************************************
     * Encodage                                                               *
     **************************************************************************/

     /**
      * Converti en encodage UTF-8 en WINDOWS-1252
      *
      * @param string $str L'entrée en UTF-8
      * @return string LA sortie en WINDOWS-1252
      */
    static private function utf8ToWindows1252(string $str): string
    {
        return iconv('UTF-8', 'WINDOWS-1252', $str);
    }

    /**
     * Converti en encodage WINDOWS-1252 en UTF-8
    *
    * @param string $str L'entrée en WINDOWS-1252
    * @return string LA sortie en UTF-8
    */
    static private function Windows1252ToUtf8(string $str): string
    {
        return iconv('WINDOWS-1252', 'UTF-8', $str);
    }

    /**
     * Réalise le rendu d'un template twig en Windows-1252
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    private function renderInWindows1252(string $template, array $data): string
    {
        $templateTwig = $this->twig->load($template);
        return self::utf8ToWindows1252($templateTwig->render($data));
    }

    /**************************************************************************
     * Travail sur les dossiers et fichiers                                   *
     **************************************************************************/

     /**
      * Permet de récupérer la valeur d'une clé dans un fichier
      *
      * @param string $file Le fichier
      * @param string $key La clé
      * @return string La valeur récupéré ou null si aucun résultat
      */
     private function getValueOfLine(string $file, string $key): string|null
     {
         // Vérifier si le fichier existe
         if ($this->filesystem->exists($file)) {
             // Lire le contenu du fichier en utilisant le bon encodage
             $contenu = self::Windows1252ToUtf8(file_get_contents($file));
 
             // Rechercher la ligne correspondante en utilisant une expression régulière
             if (preg_match('/^'.$key.'=(.*)$/m', $contenu, $matches)) {
                 return $matches[1];
             }
 
             return null;
         } else {
             throw new FileNotFoundException("Le fichier '$file' n'existe pas");
         }
     }

     /**
      * Regroupement de traitements identique sur les différents users
      *
      * @param array $conf La conf des fichiers a traiter
      * @param string $folder Le dossier dans lequel écrire
      * @param array $data Les données pour les templates
      * @param string $userType Le type d'utilisateur pour savoir quel templates prendre
      */
     private function fileProcessing(array $conf, string $folder, array $data, string $userType): void
     {
        $data = ['user' => $data];

        foreach ($conf['files_append'] as $fileName) {
            $file = $folder . '/' . $fileName;
            $template = $this->twig->load($userType . '/'.$fileName.'.twig');
            $content = self::utf8ToWindows1252($template->render($data));
            file_put_contents($file, $content , FILE_APPEND);
            $this->filesystem->chmod($file, $this->config['file_right']);
        }

        if (array_key_exists('files_create', $conf)) {
            foreach ($conf['files_create'] as $fileName) {
                $file = $folder.'/'.$fileName;
                $content = $this->renderInWindows1252($userType . '/' . $fileName . '.twig', $data);
                $file = str_replace("{uid}", $data['user']['uid'], $file);
                file_put_contents($file, $content);
                $this->filesystem->chmod($file, $this->config['file_right']);
            }
        }
     }

     /**
      * Permet de remplacer le prénom et le nom d'un utilisateur dans le fichier
      * .teacherlist ou .userlist d'un établissement ou d'une classe
      *
      * @param string $fileName Le fichier de liste a éditer
      * @param string $uid L'uid de l'utilisateur
      * @param string $firstName Le prénom de l'utilisateur
      * @param string $lastName Le nom de l'utilisateur
      * @param string $start Le début de la ligne, vide par défaut
      * @return void
      */
     private function replaceFirstNameAndLastNameInFileList(string $fileName, string $uid, string $firstName, string $lastName, string $start = ""): void
     {
         $fileName = $this->getRootFolder() . '/' . $fileName;
 
         if (!$this->filesystem->exists($fileName)) {
             throw new \Exception("Le fichier '$fileName' n'existe pas.");
         }
 
         $fileContents = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
         $search = ',' . $uid;
 
         // Parcourir chaque ligne et remplacer la ligne cible
         foreach ($fileContents as $key => $line) {
            $line = self::Windows1252ToUtf8($line);

            if (str_ends_with($line, $search)) {
                $newLine = $start . $lastName . ',' . $firstName . ',' . $uid;
                $fileContents[$key] = self::utf8ToWindows1252($newLine);
            }
         }
 
         $this->filesystem->dumpFile($fileName, implode(PHP_EOL, $fileContents) . PHP_EOL);
     }


    /**************************************************************************
     * Contrôle sur la cohérence de structure des id                          *
     **************************************************************************/

    /**
     * Permet de vérifier que le format d'un user id est valide
     *
     * @param string $uid Le user id de l'utilisateur
     * @return boolean true si le user id est valide, false sinon
     */
    private function isValidUserIdFormat(string $uid): bool
    {
        return preg_match($this->config['user_id']['regex'], $uid);
    }

    /**
     * Permet de vérifier que le format d'un id de groupement de classe est valide
     *
     * @param string $id L'id du groupement de classe
     * @return boolean true si l'id du groupement est valide, false sinon
     */
    private function isValidGroupingClassesIdFormat(string $id): bool
    {
        return preg_match($this->config['grouping_classes_id']['regex'], $id);
    }

    /**
     * Permet de vérifier que le format d'un id de classe est valide
     *
     * @param string $uid L'id de la classe
     * @return boolean true si l'id de la classe est valide, false sinon
     */
    private function isValidClassIdFormat(string $id): bool
    {
        return preg_match($this->config['class_id']['regex'], $id);
    }

    /**
     * Permet de vérifier que le format d'un nom de classe est valide
     *
     * @param string $name Le nom de la classe
     * @return boolean true si le nom de la classe est valide, false sinon
     */
    private function isValidClassName(string $name): bool
    {
        return preg_match($this->config['class_name']['regex'], $name);
    }

    /**
     * Permet de tester la validité du format d'un user id et de déclencher une
     * exception en cas d'invalidité
     *
     * @param string $uid L'id du user a tester a tester
     */
    private function testUserIdFormat(string $uid): void
    {
        if (!$this->isValidUserIdFormat($uid)) {
            throw new InvalidUserException(
                "Le format de l'id du user suivant : '$uid' est invalide"
            );
        }
    }

    /**
     * Permet de tester la validité du format de l'id d'un groupement de classes
     * et de déclencher une exception en cas d'invalidité
     *
     * @param string $id L'id du groupement de classes à tester
     */
    private function testGroupingClassesIdFormat(string $id): void
    {
        if (!$this->isValidGroupingClassesIdFormat($id)) {
            throw new InvalidGroupingClassesException(
                "Le format de l'id du groupement de classes suivant : '$id' " .
                "est invalide, il devrait être constitué de 7 chiffres"
            );
        }
    }

    /**
     * Permet de tester la validité du format de l'id d'une classes et de
     * déclencher une exception en cas d'invalidité
     *
     * @param string $id L'id de la classes à tester
     */
    private function testClassIdFormat(string $id): void
    {
        if (!$this->isValidClassIdFormat($id)) {
            throw new InvalidClassException(
                "Le format de l'id de classe suivant : '$id' est invalide"
            );
        }
    }

    /**************************************************************************
     * Vérification de l'existence des structures                             *
     **************************************************************************/

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
        $folder = $this->getRootFolder() . '/' . $id;

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
     * Permet de tester si une classe d'un groupement de classes existe
     *
     * @param string $id L'identifiant du groupement de classes
     * @param string $idClass L'identifiant de la classe
     * @return boolean true s'il existe, false sinon
     *
     * @throws InvalidGroupingClassesException Si l'identifiant du
     *  groupement de classes est mal formé ou si la structure du groupement
     *  de classes est invalide
     */
    private function isClassExist(string $id, string $idClass): bool
    {
        $folder = $this->getRootFolder() . '/' . $id . '/' . $idClass;

        // Vérification de l'existence du dossier de la classes
        if (!$this->filesystem->exists($folder)) {
            return false;
        }

        if (!is_dir($folder)) {
            throw new InvalidClassException(
                "'$folder' n'est pas un dossier de classe mais un fichier"
            );
        }

        // Vérification des sous répertoires de la classe
        foreach ($this->config['structure']['sub_folders'] as $subFolder) {
            $subFolder = $folder . '/' . $subFolder;

            if (!$this->filesystem->exists($subFolder)) {
                // Comme le sous répertoire n'existe pas, on le créé pour corriger le problème.
                $this->filesystem->mkdir($subFolder);
                /*throw new InvalidClassException(
                    "Le sous dossier '$subFolder' pour la classe '$idClass' du".
                    " groupement de classes '$id' n'existe pas"
                );*/
            }

            if (!is_dir($subFolder)) {
                throw new InvalidClassException(
                    "'$subFolder' devrait être un dossier mais est un fichier"
                );
            }
        }

        // Vérification des fichiers de la classes
        foreach ($this->config['structure']['files'] as $file) {
            $file = $folder . '/' . $file;

            if (!$this->filesystem->exists($file)) {
                throw new InvalidClassException(
                    "Le fichier '$file' pour la classe '$idClass' du".
                    " groupement de classes '$id' n'existe pas"
                );
            }

            if (!is_file($file)) {
                throw new InvalidClassException(
                    "'$file' devrait être un fichier mais est un dossier"
                );
            }
        }

        return true;
    }

    /**
     * Permet de tester l'existence d'un groupement de classes et retourne une
     * exception si elle n'existe pas
     *
     * @param string $id Identifiant du groupement de classes
     */
    private function testGroupingClassesExist(string $id): void
    {
        if (!$this->isGroupingClassesExist($id)) {
            throw new InvalidGroupingClassesException(
                "Le groupement de classes '$id' n'existe pas"
            );
        }
    }

    /**
     * Permet de tester l'existence d'une classes et retourne une exception si
     * elle n'existe pas
     *
     * @param string $id Identifiant du groupement de classes
     * @param string $idClass Identifiant de la classe
     */
    private function testClasseExist(string $id, string $idClass): void
    {
        if (!$this->isClassExist($id, $idClass)) {
            throw new InvalidClassException(
                "La classe '$idClass' du groupement de classes '$id' n'existe pas"
            );
        }
    }


    /**************************************************************************
     * Autres fonctions                                                       *
     **************************************************************************/


    /**
     * Permet de tester si le professeur existe dans le groupement de classes
     *
     * @param string $id L'identifiant du groupement de classes
     * @param string $uid L'identifiant de du professeur
     * @return boolean true s'il existe, false sinon
     */
    private function isTeacherRegisteredInGroupingClasses(string $id, string $uid): bool
    {
        return $this->isUidInFiles($uid, $this->getRootFolder() . '/' . $id, true);
    }

    /**
     * Permet de vérifier si un uid est bien présent dans un fichier list et un
     * fichier list_external
     *
     * @param string $uid L'uid a rechercher
     * @param string $folder Le dossier des fichiers
     * @param boolean $teacher Un booléen pour savoir si l'on manipule un user ou un teacher
     * @return boolean true si l'uid existe, false sinon
     */
    private function isUidInFiles(string $uid, string $folder, bool $teacher = false): bool
    {
        $fileList = $folder . "/." . ($teacher ? "teacher" : "user") . "list";
        $fileListExternal = $fileList . "_external";
        // On commence par vérifier le fichier $fileList
        $regex = '/^[^,]*,[^,]*,' . $uid . '$/m';

        // Pas besoin de conversion d'encodage car les uid n'utilisent que des caractères classiques
        if (!$this->filesystem->exists($fileList) || !preg_match($regex, file_get_contents($fileList))) {
            return false;
        }
        
        // Puis le fichier $fileListExternal
        $regex = '/^' . $uid . ':' . $uid . '$/m';

        // Pas besoin de conversion d'encodage car les uid n'utilisent que des caractères classiques
        if (!$this->filesystem->exists($fileListExternal) || !preg_match($regex, file_get_contents($fileListExternal))) {
            return false;
        }

        return true;
    }

    /**
     * Permet de tester si l'élève existe dans le groupement de classes
     *
     * @param string $id L'identifiant du groupement de classes
     * @param string $uid L'identifiant de l'élève
     * @return boolean true s'il existe, false sinon
     */
    private function isUserRegisteredInGroupingClasses(string $id, string $uid): bool
    {
        $folder = $this->getRootFolder() . '/' . $id;

        // On commence par vérifier la présence du fichier de l'utilisateur dans .users
        $file = $folder.'/.users/'.$uid;

        if (!$this->filesystem->exists($file)) {
            return false;
        }

        // Puis on vérifie sa présence dans le groupement
        if (!$this->isUidInFiles($uid, $folder)) {
            return false;
        }

        return true;
    }

    /**
     * Permet de tester si l'élève existe dans la classes
     *
     * @param string $id L'identifiant du groupement de classes
     * @param string $idClass L'identifiant de la classe
     * @param string $uid L'identifiant de l'élève
     * @return boolean true s'il existe, false sinon
     */
    private function isUserRegisteredInClass(string $id, string $idClass, string $uid): bool
    {
        $folderGroupement = $this->getRootFolder() . '/' . $id;
        $folderClass = $folderGroupement . '/' . $idClass;

        // On commence par vérifier la présence de la classe dans le fichier du
        // user
        $file = $folderGroupement.'/.users/'.$uid;
        $class = $id.'/'.$idClass;
        $regex = '#^!set user_participate=((|.+,)'.$class.'(|,.+))$#m';

        // Normalement l'existence du fichier a déjà été testé avant
        // pas besoin de conversion d'encodage car l'il de la class n'utilise que des caractères classiques
        if (!preg_match($regex, file_get_contents($file))) {
            return false;
        }

        // Et enfin on vérifie sa présence dans la classe
        if (!$this->isUidInFiles($uid, $folderClass)) {
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
        $folder = $this->getRootFolder() . '/' . $id;
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
            'user' => $dataSupervisor,
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
            $content = self::utf8ToWindows1252($template->render($data));
            file_put_contents($file, $content);
            $this->filesystem->chmod($file, $this->config['file_right']);
        }
    }

    private function fromObjGroupingClassesToDataArray(GroupingClasses $groupingClasses): array
    {
        return [
            'institution_name' => $groupingClasses->getName(),
            'description' => $groupingClasses->getUai(),
        ];
    }

    private function fromObjUserToDataArray(User $user): array
    {
        return [
            'uid' => $user->getUid(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'email' => $user->getMail(),
        ];
    }

    private function fromObjCohortToDataArray(Cohort $cohort): array
    {
        $teacher = $cohort->getTeacher();
        
        return [
            'description' => mb_substr($cohort->getName() . " - " . $teacher->getLastName(), 0, 50),
            'institution_name' => $cohort->getGroupingClasses()->getName(),
        ];
    }

    static private function comparerNomPrenom($a, $b) {
        $res = strcmp($a['nom'], $b['nom']);

        if ($res != 0) {
            return $res;
        }

        return strcmp($a['prenom'], $b['prenom']);
    }
}
