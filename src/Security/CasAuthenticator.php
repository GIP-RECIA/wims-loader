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

        $casServiceBaseUrl = $this->getParameter('casServiceBaseUrl');

        if (strpos($casServiceBaseUrl, '%DOMAIN%')) {
            $casServiceBaseUrl = str_replace('%DOMAIN%', $request->server->get('SERVER_NAME'), $casServiceBaseUrl);
        }

        \phpCAS::client(CAS_VERSION_2_0, $this->getParameter('host'), $this->getParameter('port'), is_null($this->getParameter('path')) ? '' : $this->getParameter('path'), $casServiceBaseUrl, true);
        
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
        $srcProfils = isset($src['ENTPersonProfils']) ? $src['ENTPersonProfils'] : [];
        $srcProfils = is_array($srcProfils) ? $srcProfils : [$srcProfils];
        $keyFirstName = $this->config['ticketFirstName'];
        $keyLastName = $this->config['ticketLastName'];

        return [
            'profils' => $srcProfils,
            'lastName' => isset($src[$keyLastName]) ? $src[$keyLastName] : null,
            'firstName' => isset($src[$keyFirstName]) ? $src[$keyFirstName] : null,
            'sirenCourant' => $src['ESCOSIRENCourant'],
            'mail' => $src['mail'],
        ];
    }
}
