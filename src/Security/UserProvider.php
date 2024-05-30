<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
//use User

class UserProvider extends ServiceEntityRepository implements UserProviderInterface
{
    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier, $attributs = null): UserInterface
    {
        // Dans le cas où l'on n'a pas les informations du ticket cas, on retourne un user vide
        if ($attributs == null) {
            return new User();
        }

        $em = $this->getEntityManager();
        $user = $em->getRepository(User::class)->findOneByUid($identifier);
        $firstName = substr($attributs['firstName'], 0, 60);
        $lastName = substr($attributs['lastName'], 0, 60);
        $mail = $attributs['mail'];


        if ($user === null) {
            // Si l'utilisateur n'existe pas en base, on le créé
            $user = (new User())
                ->setUid($identifier)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setMail($mail);
            $em->persist($user);
        } else {
            // L'utilisateur a été chargé de la base et on vérifie qu'il n'a pas évolué
            $user
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setMail($mail);
        }

        $em->flush();
        
        $user
            ->setSirenCourant($attributs['sirenCourant'])
            ->setTicketEnsClasses($attributs['ensClasses']);
        $roles = [];

        if ($identifier === '__NO_USER__') {
            $roles = array('ROLE_ANON');
        } else {
            $roles = array('ROLE_ANON', 'ROLE_USER');

            if ($attributs !== null) {
                if (array_key_exists('profils', $attributs)) {
                    foreach ($attributs['profils'] as $profil) {
                        switch ($profil) {
                            case 'National_ELV':
                                $roles[] = 'ROLE_ELV';
                                break;
                            case 'National_ENS':
                                $roles[] = 'ROLE_ENS';
                                break;
                            case 'National_DOC':
                                $roles[] = 'ROLE_DOC';
                                break;
                            case 'National_COL':
                                $roles[] = 'ROLE_COL';
                                break;
                        }
                    } 
                }
            }
        }

        $user->setRoles($roles);

        return $user;

        // Load a User object from your data source or throw UserNotFoundException.
        // The $identifier argument may not actually be a username:
        // it is whatever value is being returned by the getUserIdentifier()
        // method in your User class.
        //throw new \Exception('TODO: fill in loadUserByIdentifier() inside '.__FILE__);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        // Return a User object after making sure its data is "fresh".
        // Or throw a UsernameNotFoundException if the user no longer exists.
        //throw new \Exception('TODO: fill in refreshUser() inside '.__FILE__);

        return $this->loadUserByIdentifier($user->getUid());
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
