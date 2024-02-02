<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('pass_crypt', [$this, 'passCrypt']),
            new TwigFilter('bool', [$this, 'bool']),
            new TwigFilter('wims_date', [$this, 'wimsDate']),
        ];
    }

    /**
     * Hache le password
     *
     * @param string $password Le password à hacher
     * @param string $salt Le salt
     * @return string Le password haché
     */
    public function passCrypt(string $password, string $salt = 'Nv'): string
    {
        return crypt($password, $salt);
    }

    /**
     * Converti un bool en 'yes' ou 'no'
     *
     * @param boolean $bool Le booléen à convertir
     * @return string 'yes' si true, 'no' sinon
     */
    public function bool(bool $bool): string
    {
        return $bool ? 'yes' : 'no';
    }

    /**
     * Converti une date au format wims
     * 
     * @param string|\DateTime $date
     * @return string La date sous forme de string
     */
    public function wimsDate($date): string
    {
        if ($date === 'now') {
            return date('Ymd');
        }

        if ($date instanceof \DateTime) {
            return $date->format('Ymd');
        }

        return $date;
    }
}