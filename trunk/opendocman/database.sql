# MySQL dump 8.16
#--------------------------------------------------------
# Server version	4.0.12-max-log

#
# Table structure for table 'admin'
#

DROP TABLE IF EXISTS admin;
CREATE TABLE admin (
  id smallint(5) unsigned default NULL,
  admin tinyint(4) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'admin'
#

INSERT INTO admin VALUES (1,1);

#
# Table structure for table 'category'
#

DROP TABLE IF EXISTS category;
CREATE TABLE category (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'category'
#

INSERT INTO category VALUES (1,'SOP');
INSERT INTO category VALUES (2,'Training Manual');
INSERT INTO category VALUES (3,'Letter');
INSERT INTO category VALUES (4,'Presentation');


#
# Table structure for table 'data'
#

DROP TABLE IF EXISTS data;
CREATE TABLE data (
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

#
# Table structure for table 'department'
#

DROP TABLE IF EXISTS department;
CREATE TABLE department (
  id smallint(5) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'department'
#

INSERT INTO department VALUES (1,'Information Systems');


#
# Table structure for table 'dept_perms'
#

DROP TABLE IF EXISTS dept_perms;
CREATE TABLE dept_perms (
  fid smallint(5) unsigned default NULL,
  dept_id smallint(5) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0'
) TYPE=MyISAM;

#
# Dumping data for table 'dept_perms'
#


#
# Table structure for table 'dept_reviewer'
#

DROP TABLE IF EXISTS dept_reviewer;
CREATE TABLE dept_reviewer (
  dept_id smallint(5) unsigned default NULL,
  user_id smallint(5) unsigned default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'dept_reviewer'
#

INSERT INTO dept_reviewer VALUES (1,1);

#
# Table structure for table 'log'
#

DROP TABLE IF EXISTS log;
CREATE TABLE log (
  id int(10) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'log'
#


#
# Table structure for table 'rights'
#

DROP TABLE IF EXISTS rights;
CREATE TABLE rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'rights'
#

INSERT INTO rights VALUES (0,'none');
INSERT INTO rights VALUES (1,'view');
INSERT INTO rights VALUES (-1,'forbidden');
INSERT INTO rights VALUES (2,'read');
INSERT INTO rights VALUES (3,'write');
INSERT INTO rights VALUES (4,'admin');

#
# Table structure for table 'user'
#

DROP TABLE IF EXISTS user;
CREATE TABLE user (
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

#
# Dumping data for table 'user'
#

INSERT INTO user VALUES (1,'admin','','1','5555551212','myemail@asdfa.com','User','Admin');


#
# Table structure for table 'user_perms'
#

DROP TABLE IF EXISTS user_perms;
CREATE TABLE user_perms (
  fid smallint(5) unsigned default NULL,
  uid smallint(5) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights)
) TYPE=MyISAM;

