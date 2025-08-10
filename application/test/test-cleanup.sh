# Stop docker-compose apps, Remove all docker containers and volumes, then restart
# Use this while iterating over tests 
docker-compose stop
docker-compose rm --force 
docker volume rm opendocman_odm-db-data opendocman_odm-docker-configs opendocman_odm-files-data
docker-compose up -d --build
docker-compose logs -f

