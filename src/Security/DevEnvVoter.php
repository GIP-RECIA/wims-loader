<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DevEnvVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === 'IS_DEV_ENV';
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $_ENV['APP_ENV'] === 'dev';
    }
}