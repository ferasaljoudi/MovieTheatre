#!/bin/bash

# Stop and remove existing containers
docker-compose down

# Remove old Docker images related to this project
docker images -q $(docker-compose config | awk '/image:/ { print $2 }') | xargs docker rmi -f

# Pull the latest Docker images
docker-compose pull

# Build the Docker containers
docker-compose build

# Tag the Docker image for Docker Hub
docker tag movietheatre-web:latest ferasaljoudi/movietheatre-web:latest

# Push the Docker image to Docker Hub
docker push ferasaljoudi/movietheatre-web:latest

# Start the Docker containers
docker-compose up -d

# Remove dangling images
docker image prune -f

# Show the running containers
docker ps
