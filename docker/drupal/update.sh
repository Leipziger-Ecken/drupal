#!/bin/bash
cd /var/www/html
composer install
drush cr
drush cim -y
drush cr
drush updb -y
