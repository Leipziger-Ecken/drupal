#!/bin/bash
cd ..
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up --build