

OpenDocMan

version 1.1rc1

Copyright (C) Stephen Lawrence, 2000 - 2003

email: logart@users.sourceforge.net

www : http://sourceforge.net/projects/opendocman/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version
2 of the License, or (at your option) any later version.This
program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details. You
should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

Abstract

About: OpenDocMan is a full featured Web-based document management
system designed to conform to ISO 17025/IEC. It features
automatich installation, check-in/out, departmental access
control, file moderation, fine grained user access control,
and a great search function. Written in PHP, and utilizing
MySQL for the backend, this project is useful for any company
looking to keep their documentation in a centralized repository.

0.1 Requirements

* Apache Webserver 1.3.x (or any other webserver, that supports
  PHP) (http://www.apache.org/)

* MySQL Server 3.22+ (http://www.mysql.com/)

* PHP 4+ compiled with MySQL-Support (http://www.php.net/)

0.2 Update Procedure

To update your current version to the latest release, load
the setup.php page and click on the appropriate upgrade
link. (ex. http://www.mydomain.com/opendocman/setup.php)

0.3 New Installation

1. Untar files into any dir in your webserver documents dir
  (ex. /var/www)

2. Edit config.php

  (a) All parameters are commented and should be self explanatory.
    Change any that apply, especially the database parameters.

3. If you DO have database creation permissions to your MySQL
  database then you can use the automatic setup script (preferred
  method). 

  (a) Load the setup.php page and click on the new install
    link. (ex. http://www.mydomain.com/opendocman/setup.php)

  (b) Enter the username and password of a user that has database
    creation permissions for the database configured in
    config.php

  (c) Skip step 4 and move on to step 5

4. If you DO NOT have database creation permissions, be advised
  that you should be carefull in doing things manually

  (a) NOTE: The entries below are just examples. 

  (b) create a MySQL-database and MySQL-User for opendocman 

  $> mysql -u root -p

  Welcome to the MySQL monitor. 
  Commands end with ; or \g.

  Your MySQL connection id is 5525 to server version: 3.22.32

  Type 'help' for help.

  mysql> create database opendocman;

  mysql> grant select,insert,update,delete,create on opendocman.*
  to opendocman@localhost identified by 'opendocman';

  mysql> flush privileges;

  mysql> exit;

  $> mysql -u opendocman -p opendocman < database.sql

5. Make a directory for the files to be stored that is accessible
  to the web server but not available by browsing

  $>mkdir /usr/local/opendocman/data

6. Point your favorite webbrowser to the opendocman folder:
  ex. "http://www.mydomain.com/opendocman",

7. Login as "admin" (without password). After that, go to
  "admin->users->update->admin" and set your admin password.

8. Add departments, categories, users, etc.

9. Enjoy!

0.4 Developers

Developers:

* Stephen Lawrence Jr.

* Khoa Nguyen

Originally inspired by an article called cracking the vault.
