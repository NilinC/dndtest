# Librairie PHP d'une commande Symfony

La commande symfony prend en entrée un fichier plat de type CSV et affiche les données sous forme de tableau.

## Stack technique
* PHP 8.2
* Symfony 6.2
* PHPUnit 10.0

## Installer le projet
```
$ git clone https://github.com/NilinC/dndtest
$ cd dndtest/
$ composer install

// Pour lancer la commande
$ php bin/console app:read-file <lien_fichier.csv>

// Pour lancer les tests unitaires
$ php bin/phpunit
```
