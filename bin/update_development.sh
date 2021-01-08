#!/bin/bash
cd ..
git pull
sudo chown -R :33 src/drupal
sudo chmod -R g+rw src/drupal
docker-compose build drupal && \
docker-compose up --no-deps -d drupal && \
docker-compose exec -T drupal sh /update.sh
