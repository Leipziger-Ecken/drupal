#!/bin/bash
cd ..
git pull
docker-compose build drupal && \
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up --no-deps -d drupal && \
docker-compose exec -T cms sh /update.sh
