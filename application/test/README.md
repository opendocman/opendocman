# Test Suite for OpenDocMan

## Katalon Testing

OpenDocMan can be tested using the Katalon Recorder or other compatible apps.

### How-To Test

* Install the Katalon Recorder plugin for Chrome
* Copy the resources/test.txt file to /var/www/test.txt  on your mac or linux computer 
* Start a local docker-compose deployment:
  `docker-compose up -d --build`
* Load a test suite and choose a suite from the /tests folder in the opendocman codebase
* Play the test suite in Katalon
* To clean up for another thest run this:
` docker-compose stop; docker-compose rm -f; docker volume rm opendocman_odm-db-data opendocman_odm-files-data opendocman_odm-docker-configs; docker-compose up -d --build`