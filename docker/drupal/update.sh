#!/bin/bash
cd /var/www/html
composer install
drush cim -y
drush cr
drush updb -y
