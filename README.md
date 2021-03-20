# OpenDocMan

Free PHP Document Management System DMS

OpenDocMan is a web based document management system (DMS) written in PHP designed to comply with ISO 17025 and OIE standard for document management. It features fine grained control of access to files, and automated install and upgrades.

Features

    * Upload files using web browser
    * Control access to files based on department or individual user permissions
    * Track revisions of documents
    * Option to send new and updated files through review process
    * Installs on most web servers with PHP
    * Set up a review process for all new files

# License
- GPL 2.0

# Technologies
- PHP 7.4 
- Database: MySQL 8+, MariaDB 10.0+
- PHP Capable web server

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
1. Configure your web server "document root" to point to the /public folder
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
1. Configure your web server "document root" to point to the /public folder
1. Create a MySQL database/username/password
1. Make a directory for the uploaded documents to be stored that is accessible
   to the web server but not available by browsing. Ensure the
   permissions are correct on this folder to allow for the web
   server to write to it. Refer to the help text in the installer
   for more information.
   ex.  $>`mkdir /var/www/document_repository`
1. Copy the application/configs/config-sample.php to application/configs/config.php
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

## Developer Notes

### The automated installation and upgrade:
There is a folder named "application/controllers/install" which contains 
files use by the setup script. This is an automated web-based 
update/installation process. Here is how it works:

The user loads the public/index.php into their browser. They can either 
select the new installation link, or one of the upgrade links. For a new 
installation, the user will be prompted to enter a priviledged mysql username 
and password. This is for the database creation and grant assignments. The 
script will then proceed to install all the necessary data structures and 
default data entries for the most current version of ODM.

For updates, the user will be shown their current version (which comes from 
configs/config.php), and they would then click on the appropriate upgrade 
link. For example, if their version number is 1.0, they would click on the 
"Upgrade from 1.0" link. This will apply all necessary database changes to 
their current database.

For developers, when there is a new version release, a few new files need 
to be created current files modified:

1. upgrade_x.php - where x is the release name. This file should follow the 
   same format as the other upgrade_x.php files and is used for upgrades only. 
   This should be built from the output of a program like mysqldiff.pl and 
   is the difference between the new version, and the version before it.
2. setup-config.php - This is where we convert user input during the install
   into a config.php file
3. index.php - add a new function for the new version upgrade 
   (ex. "do_update_x()") where x is the release name.
   1. Inside this new function, you must "include" each previous upgrade 
      file in succession (see upgrade_10.php for an example).
   2. Add a new case statement for the new upgrade call
4. odm.php - This file contains all the current SQL commands needed to install
5. database.sql  - This should contain the same sql commands as odm.php, 
   only in a mysqldump format for users that need to manually install the 
   program for some reason.

These files MUST be kept syncronized for each release!