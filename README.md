# OpenDocMan

Free PHP Document Management System DMS

OpenDocMan is a web based document management system (DMS) written in PHP designed to comply with ISO 17025 and OIE standard for document management. It features fine grained control of access to files, and automated install and upgrades.

Features

    * Upload files using web browser
    * Control access to files based on department or individual user permissions
    * Track revisions of documents
    * Option to send new and updated files through review process
    * Installs on most web servers with PHP
    * Set up a reviewal process for all new files

# License
- GPL 2.0

# Technologies
- PHP 7.2, 7.3, 7.4 
- Database: MySQL 5.7+, MariaDB 10.0+

# Installation

## Setting Up The Database

- Login to your mysql server
- `create database opendocman;`
- `CREATE USER 'opendocman'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YOURPASSWORDHERE';`
- `GRANT ALL PRIVILEGES ON opendocman.* TO 'opendocman'@'YOURDBHOSTNAME';`

## Deploying The App

### Installing via Docker

Installing via Docker is the easiest option. This will run the database and app inside a docker-compose deployment.
The docker-configs folder and the files-data folder will be created to persist when you stop/start/update your
docker-compose deployment. 

1. Ensure you have a Docker service available
1. `docker up -d --build`

### Installing to a web server (Automatic)

1. Untar/Unzip files into any dir in your web server document home folder
1. Create a MySQL database/username/password
1. Make a directory for the uploaded documents to be stored that is accessible
   to the web server but not available by browsing. Ensure the
   permissions are correct on this folder to allow for the web
   server to write to it. Refer to the help text in the installer
   for more information.

   ex.  $>`mkdir /var/www/document_repository`

1. Load the opendocman index.php page in your web browser and follow the prompts.
1. Enjoy!


### Installing to a web server (Manual)

1. Untar/Unzip files into any dir in your web server document home folder
1. Create a MySQL database/username/password
1. Make a directory for the uploaded documents to be stored that is accessible
   to the web server but not available by browsing. Ensure the
   permissions are correct on this folder to allow for the web
   server to write to it. Refer to the help text in the installer
   for more information.

   ex.  $>`mkdir /var/www/document_repository`

1. Copy the config-sample.php to config.php
1. Edit the config.php to include your database parameters
1. Edit the database.sql file. You need to change the values set in the odm_settings table, and odm_user tables, 
   specifically the dataDir value, and the password used for the admin user creation
1. Import your database.sql file into your database
1. Visit the URL for your installation and login as admin (no password)

## Update Procedure

To update your current version to the latest release:

1. Rename your current opendocman folder.
1. Unarchive opendocman into a new folder and rename it to the original folder name
1. Copy your original config.php file into the new folder
1. Load the opendocman /install address in your web browser ( ex. http://www.example.com/install )
1. Follow the prompts for installation.
