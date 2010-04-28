# MySQL dump 8.16
#
#--------------------------------------------------------
# Server version	4.0.12-max-log

#
# Table structure for table 'odm_admin'
#

CREATE TABLE odm_admin (
  id int(11) unsigned default NULL,
  admin tinyint(4) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'odm_admin'
#

INSERT INTO odm_admin VALUES (1,1);

#
# Table structure for table 'odm_category'
#

CREATE TABLE odm_category (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'odm_category'
#

INSERT INTO odm_category VALUES (1,'SOP');
INSERT INTO odm_category VALUES (2,'Training Manual');
INSERT INTO odm_category VALUES (3,'Letter');
INSERT INTO odm_category VALUES (4,'Presentation');

#
# Table structure for table 'odm_data'
#

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
) TYPE=MyISAM;

#
# Dumping data for table 'odm_data'
#

#
# Table structure for table 'odm_department'
#

CREATE TABLE odm_department (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Dumping data for table 'odm_department'
#

INSERT INTO odm_department VALUES (1,'Information Systems');

#
# Table structure for table 'odm_dept_perms'
#

CREATE TABLE odm_dept_perms (
  fid int(11) unsigned default NULL,
  dept_id int(11) unsigned default NULL,
  rights tinyint(4) NOT NULL default '0',
  KEY rights (rights),
  KEY dept_id (dept_id),
  KEY fid (fid)
) TYPE=MyISAM;

#
# Dumping data for table 'odm_dept_perms'
#


#
# Table structure for table 'odm_dept_reviewer'
#

CREATE TABLE odm_dept_reviewer (
  dept_id int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'odm_dept_reviewer'
#

INSERT INTO odm_dept_reviewer VALUES (1,1);

#
# Table structure for table 'odm_log'
#

CREATE TABLE odm_log (
  id int(11) unsigned NOT NULL default '0',
  modified_on datetime NOT NULL default '0000-00-00 00:00:00',
  modified_by varchar(25) default NULL,
  note text,
  revision varchar(255) default NULL,
  KEY id (id),
  KEY modified_on (modified_on)
) TYPE=MyISAM;

#
# Dumping data for table 'odm_log'
#


#
# Table structure for table 'odm_rights'
#

CREATE TABLE odm_rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table 'odm_rights'
#

INSERT INTO odm_rights VALUES (0,'none');
INSERT INTO odm_rights VALUES (1,'view');
INSERT INTO odm_rights VALUES (-1,'forbidden');
INSERT INTO odm_rights VALUES (2,'read');
INSERT INTO odm_rights VALUES (3,'write');
INSERT INTO odm_rights VALUES (4,'admin');

#
# Table structure for table 'odm_user'
#

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
) TYPE=MyISAM;

#
# Dumping data for table 'odm_user'
#

INSERT INTO odm_user VALUES (1,'admin','',1,'5555551212','admin@example.com','User','Admin','');

#
# Table structure for table 'odm_user_perms'
#

CREATE TABLE odm_user_perms (
  fid int(11) unsigned default NULL,
  uid int(11) unsigned NOT NULL default '0',
  rights tinyint(4) NOT NULL default '0',
  KEY user_perms_idx (fid,uid,rights),
  KEY fid (fid),
  KEY uid (uid),
  KEY rights (rights)
) TYPE=MyISAM;

#
# Dumping data for table 'odm_user_perms'
#


# New User Defined Fields Table
#
# field_type describes what type of UDF this is. At the momment
# the valid values are:
#
#   1 = Drop down style list
#   2 = Radio Buttons
#
# table_name names the database table where the allow values are listed
#
# display_name is the label shown to the user

CREATE TABLE odm_udf
(
    id  int(11) auto_increment unique,
    table_name  varchar(16),
    display_name    varchar(16),
    field_type  int
) TYPE=MyISAM;

CREATE TABLE IF NOT EXISTS odm_odmsys
(
    id  int(11) auto_increment unique,
    sys_name  varchar(16),
    sys_value    varchar(255)
) TYPE=MyISAM;

INSERT INTO odm_odmsys VALUES ('','version','1.2.5.7');

