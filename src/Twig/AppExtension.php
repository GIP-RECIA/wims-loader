<?php
namespace App\Twig;

use App\Entity\Classes;
use App\Service\WimsUrlGeneratorService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private WimsUrlGeneratorService $wimsUrlGenerator,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('pass_crypt', [$this, 'passCrypt']),
            new TwigFilter('bool', [$this, 'bool']),
            new TwigFilter('wims_date', [$this, 'wimsDate']),
            new TwigFilter('wims_log_date_time', [$this, 'wimsLogDateTime']),
            new TwigFilter('wims_url_class_for_student', [$this, 'wimsUrlClassForStudent']),
            new TwigFilter('wims_url_class_for_teacher', [$this, 'wimsUrlClassForTeacher']),
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

    /**
     * Converti une date time au format log wims
     * 
     * @param string|\DateTime $date
     * @return string La date time sous forme de string
     */
    public function wimsLogDateTime($date): string
    {
        if ($date === 'now') {
            return date('Ymd.H.i.s');
        }

        if ($date instanceof \DateTime) {
            return $date->format('Ymd.H.i.s');
        }

        return $date;
    }

    /**
     * Génère l'url élève d'une classe a partir de l'objet de la classe
     *
     * @param Classes $class La classe
     * @return string L'url élève de la classe
     */
    public function wimsUrlClassForStudent($class): string
    {
        return $this->wimsUrlGenerator->wimsUrlClassForStudent($class);
    }

    /**
     * Génère l'url élève d'une classe a partir de l'objet de la classe
     *
     * @param Classes $class La classe
     * @return string L'url élève de la classe
     */
    public function wimsUrlClassForTeacher($class): string
    {
        return $this->wimsUrlGenerator->wimsUrlClassForTeacher($class);
    }
}