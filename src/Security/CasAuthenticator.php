<?php

namespace App\Security;

use L3\Bundle\CasGuardBundle\Security\CasAuthenticator as SecurityCasAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CasAuthenticator extends SecurityCasAuthenticator
{
    public function __construct(array $config = [], EventDispatcherInterface $eventDispatcher = null)
    {
        parent::__construct($config, $eventDispatcher);
    }

    public function authenticate(Request $request): Passport
    {
        $user = "__NO_USER__";
        
        if(!isset($_SESSION)) session_start();

	    \phpCAS::setLogger();
	    \phpCAS::setVerbose(false);

        \phpCAS::client(CAS_VERSION_2_0, $this->getParameter('host'), $this->getParameter('port'), is_null($this->getParameter('path')) ? '' : $this->getParameter('path'), $this->getParameter('casServiceBaseUrl'), true);
        
        if(is_bool($this->getParameter('ca')) && $this->getParameter('ca') == false) {
            \phpCAS::setNoCasServerValidation();
        } else {
            \phpCAS::setCasServerCACert($this->getParameter('ca'));
        }

        if($this->getParameter('handleLogoutRequest')) {
            if ($request->request->has('logoutRequest')) {
                $this->checkHandleLogout($request);
            }
            $logoutRequest = $request->request->get('logoutRequest');

            \phpCAS::handleLogoutRequests(true);
        } else {
            \phpCAS::handleLogoutRequests(false);
        }

        
        // si le mode gateway est activé..
        if ($this->getParameter('gateway')) {
            
            // .. code de pierre pelisset (pour les applis existantes...)
            
            if($this->getParameter('force')) {
                \phpCAS::forceAuthentication();
                $user = strtolower(\phpCAS::getUser());
                //$force = true;
            } else {
                //$force = false;
                //if(!isset($_SESSION['cas_user'])) {
                    $auth = \phpCAS::checkAuthentication();
                    if($auth) {
                        //$_SESSION['cas_user'] = \phpCAS::getUser();
                        $user = strtolower(\phpCAS::getUser());
                        //$_SESSION['cas_attributes'] = \phpCAS::getAttributes();
                    } else {
                        //$_SESSION['cas_user'] = false;
                        $user = "__NO_USER__";
                    }
                //}
            }
            /*if(!$force) {
                if (!$_SESSION['cas_user']) {
                    $user = "__NO_USER__";
                } else {
                    $user = $_SESSION['cas_user'];
                }
                
            }*/
            
        } else { 
        
            // .. sinon code de david .. pour les api rest / microservices et donc le nouvel ent ulille en view js notamment
            
            if($this->getParameter('force')) {
                \phpCAS::forceAuthentication();
                $user = strtolower(\phpCAS::getUser());
            } else {
                $authenticated = false;                      
                if($this->getParameter('gateway')) {
                    $authenticated = \phpCAS::checkAuthentication();
                } else {
                    $authenticated = \phpCAS::isAuthenticated();
                }
                
                //if ( (!isset($_SESSION['cas_user'])) || ( (isset($_SESSION['cas_user'])) && ($_SESSION['cas_user'] != false) ) ) {
                    
                    if($authenticated) {
                        //$_SESSION['cas_user'] = \phpCAS::getUser();
                        //$_SESSION['cas_attributes'] = \phpCAS::getAttributes();
                        //$user = $_SESSION['cas_user'];
                        $user = strtolower(\phpCAS::getUser());
                    } else {
                        $user = "__NO_USER__";
                    }
                //}
            } 
        }

        $passport = new SelfValidatingPassport(new UserBadge($user, null, $this->getCleanAttributes()), []);        

        return $passport;
    }

    /**
     * Permet de filtrer proprement les attributs du ticket qui nous intéressent
     * ENTPersonProfils, profils : le profil
     * ENTPersonNomPatro, sn, nom : le nom
     * ENTPersonAutresPrenoms, givenName, prenom : le prénom
     * ESCOSIRENCourant : Le siren de l'établissement courant
     *
     * @return array
     */
    private function getCleanAttributes(): array
    {
        $src = \phpCAS::getAttributes();
        $sirenCourant = $src['ESCOSIRENCourant'];
        $srcProfils = isset($src['ENTPersonProfils']) ? $src['ENTPersonProfils'] :
            (isset($src['profils']) ? $src['profils'] : []);
        $srcProfils = is_array($srcProfils) ? $srcProfils : [$srcProfils];

        return [
            'profils' => $srcProfils,
            'lastName' => isset($src['ENTPersonNomPatro']) ? $src['ENTPersonNomPatro'] :
                (isset($src['sn']) ? $src['sn'] : (isset($src['nom']) ? $src['nom'] : null)),
            'firstName' => isset($src['ENTPersonAutresPrenoms']) ? $src['ENTPersonAutresPrenoms'] :
                (isset($src['givenName']) ? $src['givenName'] : (isset($src['prenom']) ? $src['prenom'] : null)),
            'mail' => $src['mail'],
            'sirenCourant' => $sirenCourant,
        ];
    }
}
