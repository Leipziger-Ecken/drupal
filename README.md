## Leipziger Ecken V2

**Eine Stadtteilplattform für Leipzig.**

![Logo Leipziger Ecken dev-group](logo.png)

Drupal 8.9 distribution that works "out of the box". This (mono-)repository defines all dependencies within composer.json, provides an installation profile, German translations files, and custom Leipziger Ecken modules & theme.

Drupal 9 support targeted. Check support by enabling shipped "upgrade_status" module.

* [Public padlet](https://padlet.com/matthias75/leipzigerecken)


---------------------

 * Introduction
 * Installation
 * Tests
 * API

INTRODUCTION
---------------------

[Leipziger Ecken](https://leipziger-ecken.de) is a social network platform supporting local, socio-cultural actors such as organizations, initiatives or individuals. Published under [MIT license](https://github.com/Leipziger-Ecken/drupal/blob/master/LICENSE).

This project implements [features](https://www.drupal.org/project/features) to manage parts of the code (e.g. Content Types, Fields, Views or Metatags).

INSTALLATION
---------------------

### Requirements

 * Apache/Nginx
 * PHP >= 7.2
 * MySQL >= 5.5.3 / MariaDB >= 5.5.20
 * [Composer](https://getcomposer.org/)
 * Command Line is optional, but recommended (e.g. to manage composer or [Drupal Console](https://drupalconsole.com/))

@see [Drupal 8 System requirements](https://www.drupal.org/docs/8/system-requirements)

### Installation

 * After having cloned this repository, ensure that all required services are running, including composer.
 * Create a database (utf8_general_ci), set up a virtual host (e.g. "drupal.localhost")
 * Navigate to the path of this repository. Run
 ``` composer install ``` to install all dependencies
 * Open your browser and navigate to your drupal instance (e.g. "http://drupal.localhost"). Follow the instructions of the interactive install wizard ([walk-through](https://www.drupal.org/docs/user_guide/en/install-run.html)). You may also use the [installation routine](https://drupalconsole.com/docs/en/commands/site-install) of Drupal CLI
 * You should now be able to access and administer your Leipziger Ecken instance.

**Issues & problems**

Prior to installation, Drupal may complain about missing folder or write access to *sites/default/files*. When googling does not help, try to execute:

```
$ [sudo] mkdir /path_to_repo/web/sites/default/files
$ [sudo] chmod 775 /path_to_drupal_repo/web/sites/default/files
```
If write access to settings.php is a problem, try to execute:
```
$ cp /path_to_repo/web/sites/default.settings.php /path_to_repo/web/sites/settings.php
$ [sudo] chown apache:apache web/sites/default/settings.php
```
On **linux** systems, you may have to configure SELinux. Run:
```
sudo chcon -R -t httpd_sys_content_rw_t path_to_repo/
```
On **mac** systems it could be possible that CSS / javascript is not working properly after the installation. Therfore you have to make sure that the rights of the directory "/Applications/XAMPP/xamppfiles/htdocs/drupal/web/sites/default/files" are set to "read and write". After that navigate to Configuration (Konfigurationen) > Performance link (Leistung) and unchek "CSS-Dateien aggregieren" and
"JavaScript-Dateien aggregieren".

Choose the directory /Applications/XAMPP/xamppfiles/htdocs/drupal/web/sites/default/files and creat the directory "tmp".
Now go back to Drupal Configurations > Performance and "clear all caches" (alle Caches leeren). Afterwards check "CSS-Dateien aggregieren" and "JavaScript-Dateien aggregieren" and then "Save congigutations" (Konfiguration speichern).

@see Official [detailed Drupal 8 installation guide](https://www.drupal.org/docs/8/install) (starting from "Step 2").

### Tests

@todo

### API

A *read-only* REST-API (JSON, XML, HAL) is provided for akteur- and event-data. Read more on our [official Postman documentation](https://documenter.getpostman.com/view/10395067/SzmY92H6).

[![Run in Postman](https://run.pstmn.io/button.svg)](https://documenter.getpostman.com/view/10395067/SzmY92H6)
