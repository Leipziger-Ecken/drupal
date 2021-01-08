#!/bin/bash
cd ..
git pull
docker-compose build drupal && \
docker-compose up --no-deps -d drupal && \
docker-compose exec -T drupal sh /update.sh
