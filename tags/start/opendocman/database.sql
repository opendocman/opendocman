# MySQL dump 8.16
#
#--------------------------------------------------------
# Server version	4.0.5-beta-max

#
# Table structure for table 'admin'
#

CREATE TABLE admin (
  id int(11) default NULL,
  admin int(11) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'admin'
#

INSERT INTO admin VALUES (1,1);

#
# Table structure for table 'category'
#

CREATE TABLE category (
  id tinyint(4) unsigned NOT NULL auto_increment,
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

CREATE TABLE data (
  id tinyint(4) unsigned NOT NULL auto_increment,
  category tinyint(4) unsigned NOT NULL default '0',
  owner tinyint(4) unsigned NOT NULL default '0',
  realname varchar(255) NOT NULL default '',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  description varchar(255) default NULL,
  comment text,
  status tinyint(4) unsigned NOT NULL default '0',
  department tinyint(4) NOT NULL default '0',
  default_rights int(4) default NULL,
  publishable int(4) default NULL,
  reviewer int(4) default NULL,
  reviewer_comments varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'data'
#


#
# Table structure for table 'department'
#

CREATE TABLE department (
  id tinyint(4) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'department'
#

INSERT INTO department VALUES (1,'Information Systems');
INSERT INTO department VALUES (2,'Administration');
INSERT INTO department VALUES (3,'Toxicology');
INSERT INTO department VALUES (4,'Test Dept2');

#
# Table structure for table 'dept_perms'
#

CREATE TABLE dept_perms (
  fid tinyint(4) NOT NULL default '0',
  dept_id tinyint(4) NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0'
) TYPE=MyISAM;

#
# Dumping data for table 'dept_perms'
#

# Table structure for table 'dept_reviewer'
#

CREATE TABLE dept_reviewer (
  dept_id int(4) default NULL,
  user_id int(4) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'dept_reviewer'
#


#
# Table structure for table 'log'
#

CREATE TABLE log (
  id int(11) default NULL,
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text
) TYPE=MyISAM;

#
# Dumping data for table 'log'
#

#
# Table structure for table 'rights'
#

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

CREATE TABLE user (
  id tinyint(4) unsigned NOT NULL auto_increment,
  username varchar(25) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  department tinyint(4) NOT NULL default '0',
  phone varchar(20) default NULL,
  Email varchar(50) default NULL,
  last_name varchar(255) default NULL,
  first_name varchar(255) default NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'user'
#

INSERT INTO user VALUES (1,'admin','',1,'','','Joe','Admin');

#
# Table structure for table 'user_perms'
#

CREATE TABLE user_perms (
  fid tinyint(4) NOT NULL default '0',
  uid tinyint(4) NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0'
) TYPE=MyISAM;

#
# Dumping data for table 'user_perms'
#

