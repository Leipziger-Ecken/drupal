#!/bin/bash
cd ..
git pull
docker-compose exec -u root drupal chown -R :www-data .
docker-compose exec -u root drupal chmod -R g+rw .
docker-compose build drupal && \
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up --no-deps -d drupal && \
docker-compose exec -T drupal sh /update.sh
