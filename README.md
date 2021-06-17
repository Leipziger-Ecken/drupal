## Leipziger Ecken

**Eine Stadtteilplattform für Leipzig.**

![Logo Leipziger Ecken](logo.png)

[Leipziger Ecken](https://leipziger-ecken.de) is a social network platform for local, socio-cultural actors such as organizations, associations, initiatives and artists. Besides managing multipe actor profiles and adding events, users can also manage their own website using the build-in **custom webbuilder**. Leipziger Ecken is free software published under [MIT license](https://github.com/Leipziger-Ecken/drupal/blob/master/LICENSE).

---------------------

 * Introduction
 * Installation
 * [JSON:API](docu/API.md)
 * [Handbuch (German only)](docu/Handbuch.md)

INTRODUCTION
---------------------

This project ships a custom installation profile, modules and themes. All dependencies (+ patches) are managed via Composer. To synchronize core & custom configurations (e.g. content-types, permissions, etc.) we adapt Drupal [Configuration Manager](https://www.drupal.org/docs/configuration-management/managing-your-sites-configuration) to smoothly im- and export system configurations. All configuration files are stored unter *src/drupal/config*. It is highly recommended to install [drush](https://docs.drush.org/en/8.x/install/) for a straightforward developer experience.

Drupal 9 support coming soon. Meanwhile, check support by enabling (and running) the shipped "upgrade_status" module.

A previous version for Drupal 7 was once developed [here](https://github.com/JuliAne/easteasteast).

INSTALLATION
---------------------

Leipziger Ecken can either be run on a regular [LAMP](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-ubuntu-18-04)/WAMP-stack or as an "out-of-the-box" solution via [docker](https://www.docker.com/). As a rule of thumb it is recommended to using the former approach when you have no docker-experiences yet.

* [Installation via Apache/Nginx](docu/Installation_apache.md)
* [Installation via Docker](docu/Installation_docker.md)

SUPPORT
---------------------

We are thankful for any kind of support! If you would like to contribute, feel free to [get in contact](https://leipziger-ecken.de/kontakt).
