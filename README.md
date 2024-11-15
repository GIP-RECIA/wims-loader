# Wims-loader

## Description

Wims-loader est un système prévu pour venir en amont de Wims afin de gérer la
création des classes pour les enseignants et gérer l'insertion des élèves dedans
en se basant sur un annuaire.

Il permet aussi de simplifier le processus de connexion et l'intégration dans un
ENT en se basant sur une connexion CAS et en listant les classes d'un élèves
pour un accès facilité.

## Configuration

### Fonctionnement global

Wims-loader est prévu pour tourner sur le même serveur que wims avec :
* wims : `/wims/`
* wims-loader : `/vims-loader/`

### Fichier .env.local

Se baser sur le fichier `.env` comme exemple.

Voici les variables a définir dans ce fichier et leurs fonctions :
* `APP_ENV` : A passer sur `prod` pour de la production
* `APP_SECRET` : Permet de chiffrer les données des cookies, il faut donc en définir un de 32 char hexa aléatoire.
* `DATABASE_URL` : Url de connexion a la BDD
* `CAS_*` : Les informations relatives au CAS
* `LDAP_*` : Les informations relatives au LDAP
* `TRUSTED_PROXIES` : Les IP des proxies utilisés pour accéder au projet
* `SUPERVISOR_*` : Les informations du superviseur de la plate-forme
* `CAS` : Url complète du CAS
* `AUTO_REDIRECT_STUDENT` : Permet de préciser si l'étudiant est automatiquement redirigé vers la classe Wims quand il ne possède qu'une classe/groupe pédagogique. Inutile dans ce cas de lui lister ses classes.
* `ADMIN_UID` : L'uid de l'utilisateur qui est admin de Wims-loader
* `CLASSES_EXPIRATION_DATE` : La date d’expiration des classes, a changer tous les ans
* `ENT_NETOCENTRE` : A passer a true uniquement dans le cas de l'ENT Netocentre
* `GLOBAL_MESSAGE` : Si cette variable existe, contient un message qui s'affichera sur toutes les pages
* `MAINTENANCE_MODE` : Si cette variable existe et qu'elle vaut `true`, active le mode maintenance qui bloque toutes les pages pour afficher un message de maintenance
* `MAINTENANCE_MESSAGE` : Si cette variable existe, contient un message de maintenance qui vient remplacer celui par défaut quand le mode maintenance est actif
