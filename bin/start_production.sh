#!/bin/bash
cd ..
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
