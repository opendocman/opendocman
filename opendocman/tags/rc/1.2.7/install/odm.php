<?php
/*
odm.php - main file for creating a fresh installation
Copyright (C) 2002-2007  Stephen Lawrence

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// For ODM fresh install
// Admin table
$result = mysql_query("
DROP TABLE IF EXISTS odm_admin
") or die("<br>Could not create odm_admin table" .  mysql_error());

$result = mysql_query("
CREATE TABLE odm_admin (
  id int(11) unsigned default NULL,
  admin tinyint(4) default NULL
) TYPE=MyISAM
") or die("<br>Could not create odm_admin table" .  mysql_error());

// Admin user
$result = mysql_query("
INSERT INTO odm_admin VALUES (1,1)
") or die("<br>Could not create odm_admin user");

// Category table
$result = mysql_query("
DROP TABLE IF EXISTS odm_category
") or die("<br>Could not create odm_category table" .  mysql_error());

$result = mysql_query("
CREATE TABLE odm_category (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM
") or die("<br>Could not create odm_category table");

$result = mysql_query("
INSERT INTO odm_category VALUES (1,'SOP')
") or die("<br>Could not create odm_category");

$result = mysql_query("
 INSERT INTO odm_category VALUES (2,'Training Manual')
") or die("<br>Could not create odm_category");

$result = mysql_query("
 INSERT INTO odm_category VALUES (3,'Letter')
") or die("<br>Could not create odm_category");

$result = mysql_query("
 INSERT INTO odm_category VALUES (4,'Presentation')
") or die("<br>Could not create odm_category");

// Data table
$result = mysql_query("
DROP TABLE IF EXISTS odm_data
") or die("<br>Could not create odm_data table");

$result = mysql_query("
CREATE TABLE odm_data (
  id int(11) unsigned NOT NULL auto_increment,
  category int(11) unsigned NOT NULL default '0',
  owner int(11) unsigned default NULL,
  realname varchar(255) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  description varchar(255) default NULL,
  comment varchar(255) NOT NULL default '',
  status smallint(6) default NULL,
  department smallint(6) unsigned default NULL,
  default_rights tinyint(4) default NULL,
  publishable tinyint(4) default NULL,
  reviewer int(11) unsigned default NULL,
  reviewer_comments varchar(255) default NULL,
  PRIMARY KEY  (id),
  KEY data_idx (id,owner),
  KEY id (id),
  KEY id_2 (id),
  KEY publishable (publishable),
  KEY description (description)
) TYPE=MyISAM
") or die("<br>Could not create odm_data table");

// Department Table
$result = mysql_query("
DROP TABLE IF EXISTS odm_department
") or die("<br>Could not create odm_department table");

$result = mysql_query("
CREATE TABLE odm_department (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM
") or die("<br>Could not create odm_department table");

$result = mysql_query("
INSERT INTO odm_department VALUES (1,'Information Systems')
") or die("<br>Could not odm_add department");

// Department Permissions table
$result = mysql_query("
DROP TABLE IF EXISTS odm_dept_perms
") or die("<br>Could not create odm_dept_perms table");

$result = mysql_query("
CREATE TABLE odm_dept_perms (
  fid int(11) unsigned default NULL,
  dept_id int(11) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0',
  KEY rights (rights),
  KEY dept_id (dept_id),
  KEY fid (fid)
) TYPE=MyISAM
") or die("<br>Could not create odm_dept_perms table");

// Department Reviewer table
$result = mysql_query("
DROP TABLE IF EXISTS odm_dept_reviewer
") or die("<br>Could not create odm_dept_reviewer table");

$result = mysql_query("
CREATE TABLE odm_dept_reviewer (
  dept_id int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL
) TYPE=MyISAM
") or die("<br>Could not create odm_dept_reviewer table");

// data for table 'dept_reviewer'
$result = mysql_query("
INSERT INTO odm_dept_reviewer VALUES (1,1)
") or die("<br>Could add to odm_dept_reviewer table");

// Log table
$result = mysql_query("
DROP TABLE IF EXISTS odm_log
") or die("<br>Could not create odm_log table");

$result = mysql_query("
CREATE TABLE odm_log (
  id int(11) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL,
  KEY id (id),
  KEY modified_on (modified_on)
) TYPE=MyISAM
") or die("<br>Could not create odm_log table");

// Rights table
$result = mysql_query("
DROP TABLE IF EXISTS odm_rights
") or die("<br>Could not create odm_rights table");

$result = mysql_query("
CREATE TABLE odm_rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
) TYPE=MyISAM
") or die("<br>Could not create odm_rights table");

// Rights values
$result = mysql_query("
 INSERT INTO odm_rights VALUES (0,'none')
") or die("<br>Could not add odm_rights entry");

$result = mysql_query("
 INSERT INTO odm_rights VALUES (1,'view')
") or die("<br>Could not add odm_rights entry");

$result = mysql_query("
 INSERT INTO odm_rights VALUES (-1,'forbidden')
") or die("<br>Could not add odm_rights entry");

$result = mysql_query("
 INSERT INTO odm_rights VALUES (2,'read')
") or die("<br>Could not add odm_rights entry");

$result = mysql_query("
 INSERT INTO odm_rights VALUES (3,'write')
") or die("<br>Could not add odm_rights entry");

$result = mysql_query("
 INSERT INTO odm_rights VALUES (4,'admin')
") or die("<br>Could not add odm_rights entry");

// User table
$result = mysql_query("
DROP TABLE IF EXISTS odm_user
") or die("<br>Could not create odm_user table");

$result = mysql_query("
CREATE TABLE odm_user (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(25) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  department int(11) unsigned default NULL,
  phone varchar(20) default NULL,
  Email varchar(50) default NULL,
  last_name varchar(255) default NULL,
  first_name varchar(255) default NULL,
  pw_reset_code char(32) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM
") or die("<br>Could not create odm_user table");

// Create admin user
$result = mysql_query("
INSERT INTO odm_user VALUES (1,'admin','','1','5555551212','admin@example.com','User','Admin','')
") or die("<br>Could not add user");

// User permissions table
$result = mysql_query("
DROP TABLE IF EXISTS odm_user_perms
") or die("<br>Could not create odm_user_perms table");

$result = mysql_query("
CREATE TABLE IF NOT EXISTS odm_user_perms (
  fid int(11) unsigned default NULL,
  uid int(11) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights),
  KEY fid (fid),
  KEY uid (uid),
  KEY rights (rights)
) TYPE=MyISAM
") or die("<br>Could not create odm_user_perms table");

$result = mysql_query("
CREATE TABLE IF NOT EXISTS odm_udf (
    id  int auto_increment unique,
    table_name varchar(16),
    display_name varchar(16),
    field_type int
) TYPE=MyISAM
") or die("<br>Could not create odm_udf table");
?>
