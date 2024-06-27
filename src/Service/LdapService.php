<?php
namespace App\Service;

use App\Enum\CohortType;
use App\Exception\InvalidGroupingClassesException;
use App\Exception\LdapResultException;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;

/**
 * Class permettant de faire les requêtes ldap
 */
class LdapService
{
    const USER = 'ou=people,dc=esco-centre,dc=fr';
    const STRUCTURE = 'ou=structures,dc=esco-centre,dc=fr';
    private $ldap;

    public function __construct(array $config = [])
    {
        $configLdap = ['host', 'port', 'encryption'];
        $this->ldap = Ldap::create('ext_ldap', array_intersect_key($config, array_flip($configLdap)));

        try {
            $this->ldap->bind($config['dn'], $config['password']);
        } catch (\Exception $e) {
            // Gérer les erreurs de connexion LDAP
            throw $e;
        }
    }

    public function findOneUserByUid(string $uid): Entry
    {
        $results = $this
            ->search(self::USER, "(uid=$uid)")
            ->toArray();
        $count = count($results);

        if ($count !== 1) {
            throw new LdapResultException("Le résultat de la requête ldap devrait contenir les données d'un utilisateur, mais elle en a $count");
        }

        return $results[0];
    }

    /**
     * Retourne un tableau d'Entry représentant les données des utilisateurs
     * dont les uids ont été fournit dans un tableau en entrée
     *
     * @param string[] $uid
     * @return Entry[]
     */
    public function findUsersByUid(array $arrUid): array
    {
        $strSearch = '';

        foreach ($arrUid as $uid) {
            $strSearch .= "(uid=$uid)";
        }

        $strSearch = "(|" . $strSearch . ")";

        return $this->search(self::USER, $strSearch)->toArray();
    }

    /**
     * Retourne un tableau d'Entry représentant les données des utilisateurs
     * d'une cohort d'un établissement
     *
     * @param string $siren Le siren de l'établissement courant
     * @param string $cohort Le nom de la cohort
     * @param CohortType $type Le type de la cohorte
     * @return Entry[]
     */
    public function findStudentsBySirenAndCohortName(string $siren, string $class, CohortType $type): array
    {
        $attr = $type === CohortType::TYPE_CLASS ? "ENTEleveClasses" : "ENTEleveGroupes";
        
        return $this
            ->search(self::USER, "($attr=ENTStructureSIREN=$siren,ou=structures,dc=esco-centre,dc=fr\$$class)")
            ->toArray();
    }

    /**
     * Permet de récupérer un grouping classes (un établissement) avec son siren
     *
     * @param string $siren Le siren a rechercher
     * @return Entry L'entry du grouping classes
     */
    public function findOneGroupingClassesBySiren(string $siren): Entry
    {
        $results = $this
            ->search(self::STRUCTURE, "(&(ObjectClass=ENTEtablissement)(ENTStructureSiren=$siren))")
            ->toArray();
        $count = count($results);

        if ($count === 0) {
            throw new InvalidGroupingClassesException(
                "Le groupement de classes (établissement) avec le siren '$siren' n'existe pas dans le ldap"
            );
        } else if ($count !== 1) {
            throw new LdapResultException(
                "Le résultat de la requête ldap devrait contenir les données d'un établissement, mais elle en a $count"
            );
        }

        return $results[0];
    }

    private function search($baseDn, $filter): CollectionInterface
    {
        try {
            $query = $this->ldap->query($baseDn, $filter);
            $results = $query->execute();

            return $results;
        } catch (\Exception $e) {
            // Gérer les erreurs de connexion LDAP
            throw $e;
        }
    }
}