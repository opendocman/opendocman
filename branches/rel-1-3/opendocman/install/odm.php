<?php
// For version ODM 1.2rc1 fresh install
// Admin table

$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "admin";
$result = mysql_query($sql) or die("<br>Could not drop admin table" .  mysql_error());

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] ."admin 
(id smallint(5) unsigned default NULL,
admin tinyint(4) default NULL 
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create admin table" .  mysql_error());

// Admin user
$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "admin VALUES (1,1)";
$result = mysql_query($sql) or die("<br>Could not create admin user");

// Category table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "category";
$result = mysql_query($sql) or die("<br>Could not drop category table" .  mysql_error());

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "category (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;";
$result = mysql_query($sql) or die("<br>Could not create category table");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "category VALUES (1,'SOP')";
$result = mysql_query($sql) or die("<br>Could not create category");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "category VALUES (2,'Training Manual')";
$result = mysql_query($sql) or die("<br>Could not create category");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "category VALUES (3,'Letter')";
$result = mysql_query($sql) or die("<br>Could not create category");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "category VALUES (4,'Presentation')";
$result = mysql_query($sql) or die("<br>Could not create category");

// Data table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "data";
$result = mysql_query($sql) or die("<br>Could not drop data table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "data (
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
  anonymous tinyint default '0' NULL,
  PRIMARY KEY  (id),
  KEY data_idx (id,owner),
  KEY id (id),
  KEY id_2 (id),
  KEY publishable (publishable),
  KEY description (description)
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create data table");

// Department Table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "department";
$result = mysql_query($sql) or die("<br>Could not drop department table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "department (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;";
$result = mysql_query($sql) or die("<br>Could not create department table");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "department VALUES (1,'Information Systems')";
$result = mysql_query($sql) or die("<br>Could not add department");

// Department Permissions table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "dept_perms";
$result = mysql_query($sql) or die("<br>Could not drop dept_perms table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "dept_perms (
  fid smallint(5) unsigned default NULL,
  dept_id smallint(5) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0',
  KEY rights (rights),
  KEY dept_id (dept_id),
  KEY fid (fid)
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create dept_perms table");

// Department Reviewer table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer";
$result = mysql_query($sql) or die("<br>Could not drop dept_reviewer table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer (
  dept_id smallint(5) unsigned default NULL,
  user_id smallint(5) unsigned default NULL
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create dept_reviewer table");

// data for table 'dept_reviewer'
$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer VALUES (1,1)";
$result = mysql_query($sql) or die("<br>Could add to dept_reviewer table");

// Log table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "log";
$result = mysql_query($sql) or die("<br>Could not drop log table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "log (
  id int(10) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL,
  KEY id (id),
  KEY modified_on (modified_on)
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create log table");

// Rights table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "rights";
$result = mysql_query($sql) or die("<br>Could not drop rights table");

$sql = "
CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create rights table");

// Rights values
$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (0,'none')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (1,'view')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (-1,'forbidden')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (2,'read')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (3,'write')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "rights VALUES (4,'admin')";
$result = mysql_query($sql) or die("<br>Could not add rights entry");

// User table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "user";
$result = mysql_query($sql) or die("<br>Could not drop user table");

$sql = "CREATE TABLE " . $GLOBALS['CONFIG']['table_prefix'] . "user (
  id smallint(5) unsigned NOT NULL auto_increment,
  username varchar(25) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  department smallint(5) unsigned default NULL,
  phone varchar(20) default NULL,
  Email varchar(50) default NULL,
  last_name varchar(255) default NULL,
  first_name varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create user table");

// Create admin user
$sql = "INSERT INTO " . $GLOBALS['CONFIG']['table_prefix'] . "user VALUES (1,'admin','','1','5555551212','admin@example.com','User','Admin')";
$result = mysql_query($sql) or die("<br>Could not add user");

// User permissions table
$sql = "DROP TABLE IF EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "user_perms";
$result = mysql_query($sql) or die("<br>Could not drop user_perms table");

$sql = "CREATE TABLE IF NOT EXISTS " . $GLOBALS['CONFIG']['table_prefix'] . "user_perms (
  fid smallint(5) unsigned default NULL,
  uid smallint(5) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights),
  KEY fid (fid),
  KEY uid (uid),
  KEY rights (rights)
) TYPE=MyISAM";
$result = mysql_query($sql) or die("<br>Could not create user_perms table");

?>
