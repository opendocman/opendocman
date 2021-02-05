opendocman
==========

OpenDocMan - Free PHP Document Management System DMS

OpenDocMan is a web based document management system (DMS) written in PHP designed to comply with ISO 17025 and OIE standard for document management. It features fine grained control of access to files, and automated install and upgrades.

Features

    * Upload files using web browser
    * Control access to files based on department or individual user permissions
    * Track revisions of documents
    * Option to send new and updated files through review process
    * Installs on most web servers with PHP
    * Set up a reviewal process for all new files

# Installation

## Installing To A Web Server

### Setting Up The Database

- Login to your mysql server
- `create database opendocman;`
- `CREATE USER 'opendocman'@'localhost' IDENTIFIED WITH mysql_native_password BY 'YOURPASSWORDHERE';`
- `GRANT ALL PRIVILEGES ON opendocman.* TO 'opendocman'@'YOURDBHOSTNAME';`

### Deploying The App

- Unzip the contents of the OpenDocMan release into a web-accessible folder on a PHP web server
- Visit the URL pointing to the web-accessible folder and follow the instructions


## Installing via Docker

- `docker up -d --build`
