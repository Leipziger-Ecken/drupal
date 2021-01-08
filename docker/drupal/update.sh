#!/bin/bash
cd /var/www/html
drush cr
drush cim -y
drush cr
drush updb -y
