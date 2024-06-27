<?php

namespace App\Enum;

/**
 * Liste les différent type de cohortes
 */
enum CohortType: int
{
    // Les classes
    case TYPE_CLASS = 1;
    // Les groupes pédagogiques
    case TYPE_GROUP = 2;
}