<?php
// Admin table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS admin (
  id smallint(5) unsigned default NULL,
  admin tinyint(4) default NULL
) TYPE=MyISAM;
") or die("<br>Could not create admin table" .  mysql_error());

// Admin user
$result = mysql_query("
INSERT INTO admin VALUES (1,1);
") or die("<br>Could not create admin user");

// Category table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS category (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
") or die("<br>Could not create category table");

$result = mysql_query("
INSERT INTO category VALUES (1,'SOP');
") or die("<br>Could not create category");

$result = mysql_query("
INSERT INTO category VALUES (2,'Training Manual');
") or die("<br>Could not create category");

$result = mysql_query("
INSERT INTO category VALUES (3,'Letter');
") or die("<br>Could not create category");

$result = mysql_query("
INSERT INTO category VALUES (4,'Presentation');
") or die("<br>Could not create category");

// Data table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS data (
  id smallint(5) unsigned NOT NULL auto_increment,
  category tinyint(4) unsigned NOT NULL default '0',
  owner smallint(6) unsigned default NULL,
  realname varchar(255) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  description varchar(255) default NULL,
  comment varchar(255) NOT NULL default '',
  status smallint(6) default NULL,
  department smallint(6) unsigned default NULL,
  default_rights tinyint(4) default NULL,
  publishable tinyint(4) default NULL,
  reviewer smallint(6) unsigned default NULL,
  reviewer_comments varchar(255) default NULL,
  filesize bigint(20) default NULL,
  PRIMARY KEY  (id),
  KEY data_idx (id,owner),
  KEY id (id),
  KEY id_2 (id)
) TYPE=MyISAM;
") or die("<br>Could not create data table");

// Department Table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS department (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
") or die("<br>Could not create department table");

$result = mysql_query("
INSERT INTO department VALUES (1,'Information Systems');
") or die("<br>Could not add department");

// Department Permissions table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS dept_perms (
  fid smallint(5) unsigned default NULL,
  dept_id smallint(5) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0'
) TYPE=MyISAM;
") or die("<br>Could not create dept_perms table");

// Department Reviewer table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS dept_reviewer (
  dept_id smallint(5) unsigned default NULL,
  user_id smallint(5) unsigned default NULL
) TYPE=MyISAM;
") or die("<br>Could not create dept_reviewer table");

// Log table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS log (
  id int(10) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL
) TYPE=MyISAM;
") or die("<br>Could not create log table");

// Rights table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
) TYPE=MyISAM;
") or die("<br>Could not create rights table");

// Rights values
$result = mysql_query("
INSERT INTO rights VALUES (0,'none');
") or die("<br>Could not add rights entry");

$result = mysql_query("
INSERT INTO rights VALUES (1,'view');
") or die("<br>Could not add rights entry");

$result = mysql_query("
INSERT INTO rights VALUES (-1,'forbidden');
") or die("<br>Could not add rights entry");

$result = mysql_query("
INSERT INTO rights VALUES (2,'read');
") or die("<br>Could not add rights entry");

$result = mysql_query("
INSERT INTO rights VALUES (3,'write');
") or die("<br>Could not add rights entry");

$result = mysql_query("
INSERT INTO rights VALUES (4,'admin');
") or die("<br>Could not add rights entry");

// User table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS user (
  id smallint(5) unsigned NOT NULL auto_increment,
  username varchar(25) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  department smallint(5) unsigned default NULL,
  phone varchar(20) default NULL,
  Email varchar(50) default NULL,
  last_name varchar(255) default NULL,
  first_name varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;
") or die("<br>Could not create user table");

// Create admin user
$result = mysql_query("
INSERT INTO user VALUES (1,'admin','','1','5555551212','myemail@asdfa.com','User','Admin');
") or die("<br>Could not add user");

// User permissions table
$result = mysql_query("
CREATE TABLE IF NOT EXISTS user_perms (
  fid smallint(5) unsigned default NULL,
  uid smallint(5) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights)
) TYPE=MyISAM;
") or die("<br>Could not create user_perms table");

?>
