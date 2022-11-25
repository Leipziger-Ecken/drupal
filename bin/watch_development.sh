#!/bin/bash
cd ..
docker-compose exec -u www-data -T drupal sh /watch.sh
