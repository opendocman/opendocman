# MySQL dump of OpenDocMan
#
#--------------------------------------------------------

#
# Table structure for table 'odm_access_log'
#

CREATE TABLE `odm_access_log` (
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL
);

#
# Table structure for table 'odm_admin'
#

CREATE TABLE odm_admin (
  id int(11) unsigned default NULL,
  admin tinyint(4) default NULL
);

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
);

#
# Dumping data for table 'odm_category'
#

INSERT INTO odm_category VALUES (NULL,'SOP');
INSERT INTO odm_category VALUES (NULL,'Training Manual');
INSERT INTO odm_category VALUES (NULL,'Letter');
INSERT INTO odm_category VALUES (NULL,'Presentation');

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
  comment varchar(255) default '',
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
);

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
);

#
# Dumping data for table 'odm_department'
#

INSERT INTO odm_department VALUES (NULL,'Information Systems');

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
);

#
# Dumping data for table 'odm_dept_perms'
#


#
# Table structure for table 'odm_dept_reviewer'
#

CREATE TABLE odm_dept_reviewer (
  dept_id int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL
);

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
);

#
# Dumping data for table 'odm_log'
#


#
# Table structure for table 'odm_rights'
#

CREATE TABLE odm_rights (
  RightId tinyint(4) default NULL,
  Description varchar(255) default NULL
);

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
);

#
# Dumping data for table 'odm_user'
#

INSERT INTO odm_user VALUES (NULL,'admin','',1,'5555551212','admin@example.com','User','Admin','');

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
);

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
);

CREATE TABLE IF NOT EXISTS odm_odmsys
(
    id  int(11) auto_increment unique,
    sys_name  varchar(16),
    sys_value    varchar(255)
);

INSERT INTO odm_odmsys VALUES (NULL,'version','1.2.6');

CREATE TABLE IF NOT EXISTS `odm_settings` (
`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
`name` VARCHAR( 255 ) NOT NULL ,
`value` VARCHAR( 255 ) NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`validation` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` ) ,
UNIQUE ( `name` )
);

INSERT INTO `odm_settings` VALUES(NULL,'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '');
INSERT INTO `odm_settings` VALUES(NULL,'title', 'Document Repository', 'This is the browser window title', 'maxsize=255');
INSERT INTO `odm_settings` VALUES(NULL,'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req');
INSERT INTO `odm_settings` VALUES(NULL,'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req');
INSERT INTO `odm_settings` VALUES(NULL,'dataDir', '/var/www/document_repository/', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255');
INSERT INTO `odm_settings` VALUES(NULL,'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255');
INSERT INTO `odm_settings` VALUES(NULL,'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255');
INSERT INTO `odm_settings` VALUES(NULL,'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num');
INSERT INTO `odm_settings` VALUES(NULL,'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'secureurl', 'True', 'Secure URL control: On or Off (case sensitive). When set to \"On\", all urls will be secured. When set to \"Off\", all urls are normal and readable', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'allow_signup', 'False', 'Should we display the sign-up link?', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool');
INSERT INTO `odm_settings` VALUES(NULL,'theme', 'tweeter', 'Which theme to use?', '');
INSERT INTO `odm_settings` VALUES(NULL,'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req');
INSERT INTO `odm_settings` VALUES(NULL,'base_url', 'http://localhost/opendocman', 'Set this to the url of the site. No need for trailing \"/\" here', 'url');

CREATE  TABLE IF NOT EXISTS `odm_filetypes` (
`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`type` VARCHAR(255) NOT NULL ,
`active` TINYINT(4) NOT NULL ,
PRIMARY KEY (`id`)
);

INSERT INTO `odm_filetypes` VALUES(NULL, 'image/gif', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'text/html', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'text/plain', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/pdf', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/x-pdf', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/msword', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/jpeg', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/pjpeg', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/png', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/msexcel', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/msaccess', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'text/richtxt', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/mspowerpoint', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/octet-stream', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/x-zip-compressed', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/tiff', 1);
INSERT INTO `odm_filetypes` VALUES(NUll, 'image/tif', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.ms-powerpoint', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.ms-excel', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-master', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-template', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-web', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'text/csv', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'audio/mpeg', 0);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/x-dwg', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'image/x-dfx', 1);
INSERT INTO `odm_filetypes` VALUES(NULL, 'drawing/x-dwf', 1);
