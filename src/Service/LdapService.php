<?php
namespace App\Service;

use Symfony\Component\Ldap\Ldap;

class LdapService
{
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

    public function search($baseDn, $filter)
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