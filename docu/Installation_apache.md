## Installing & running Leipziger Ecken via LAMP-/WAMP-stack

### Requirements

 * Apache/Nginx
 * PHP >= 7.2
 * MySQL >= 5.5.3 / MariaDB >= 5.5.20
 * [Composer](https://getcomposer.org/)
 * Command Line is optional, but recommended (e.g. to manage composer or [Drupal Console](https://drupalconsole.com/))

@see [Drupal 8 System requirements](https://www.drupal.org/docs/8/system-requirements)

### Installation

 * Clone this repository, ensure that all required services are running, including composer
 * Create a database (utf8_general_ci), set up a virtual host (e.g. "drupal.localhost")
 * Navigate to the local drupal-folder: ```cd src/drupal```
 * Run ``` composer install ``` to install all dependencies
 * **Alerta alerta:** Composer may complain about not being able to *"apply the patch xy"*. This is a [known bug](https://github.com/cweagans/composer-patches/issues/226). Just apply each patch manually by running
 ``` git apply patches/name-of-patch.patch ```, e.g. ```git apply patches/mapbox-provider-locality.patch```.
 * Open your browser and navigate to your drupal instance (e.g. "http://drupal.localhost"). Follow the instructions of the interactive install wizard ([walk-through](https://www.drupal.org/docs/user_guide/en/install-run.html)). You may also use the installation routine of [Drupal CLI](https://drupalconsole.com/docs/en/commands/site-install) or drush.
 * You should now be able to access and administer your Leipziger Ecken instance.

**Issues & problems**

Prior to installation, Drupal may complain about missing folder or write access to *sites/default/files*. Run:
```
$ [sudo] mkdir /path_to_repo/web/sites/default/files
$ [sudo] chmod 775 /path_to_drupal_repo/web/sites/default/files
```

If write access to settings.php is a problem:
```
$ cp /path_to_repo/web/sites/default.settings.php /path_to_repo/web/sites/settings.php
$ [sudo] chown apache:apache web/sites/default/settings.php
```

On **linux** systems, you may have to configure SELinux:
```
sudo chcon -R -t httpd_sys_content_rw_t path_to_repo/
```

On **mac** systems it could be possible that CSS / javascript is not working properly after the installation. Therfore you have to make sure that the rights of the directory "/Applications/XAMPP/xamppfiles/htdocs/drupal/web/sites/default/files" are set to "read and write". After that navigate to Configuration (Konfigurationen) > Performance link (Leistung) and uncheck "CSS-Dateien aggregieren" and "JavaScript-Dateien aggregieren".

Choose the directory /Applications/XAMPP/xamppfiles/htdocs/drupal/web/sites/default/files and creat the directory "tmp".
Now go back to Drupal Configurations > Performance and "clear all caches" (alle Caches leeren). Afterwards check "CSS-Dateien aggregieren" and "JavaScript-Dateien aggregieren" and then "Save congigutations" (Konfiguration speichern).

@see Official [detailed Drupal 8 installation guide](https://www.drupal.org/docs/8/install) (starting from "Step 2"). 
