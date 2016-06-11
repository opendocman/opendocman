# MySQL dump of OpenDocMan
#
#--------------------------------------------------------

#
# Table structure for table 'odm_access_log'
#

CREATE TABLE IF NOT EXISTS `odm_access_log` (
  `file_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `action` enum('A','B','C','V','D','M','X','I','O','Y','R') NOT NULL,
  PRIMARY KEY ( `file_id`, `user_id`, `timestamp`, `action` )
) ENGINE = MyISAM;

#
# Table structure for table 'odm_admin'
#

CREATE TABLE IF NOT EXISTS `odm_admin` (
  `id` INT(11) UNSIGNED DEFAULT NULL,
  `admin` TINYINT(1) DEFAULT NULL,
  PRIMARY KEY ( `id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_admin'
#

INSERT INTO `odm_admin` VALUES (1,1);

#
# Table structure for table 'odm_category'
#

CREATE TABLE IF NOT EXISTS `odm_category` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_category'
#

INSERT INTO `odm_category` VALUES (NULL,'SOP');
INSERT INTO `odm_category` VALUES (NULL,'Training Manual');
INSERT INTO `odm_category` VALUES (NULL,'Letter');
INSERT INTO `odm_category` VALUES (NULL,'Presentation');

#
# Table structure for table 'odm_data'
#

CREATE TABLE IF NOT EXISTS `odm_data` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `owner` INT(11) UNSIGNED DEFAULT NULL,
  `realname` VARCHAR(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `description` VARCHAR(255) DEFAULT NULL,
  `comment` VARCHAR(255) DEFAULT '',
  `status` SMALLINT(6) DEFAULT NULL,
  `department` SMALLINT(6) UNSIGNED DEFAULT NULL,
  `default_rights` TINYINT(1) DEFAULT NULL,
  `publishable` TINYINT(1) DEFAULT NULL,
  `reviewer` INT(11) UNSIGNED DEFAULT NULL,
  `reviewer_comments` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY  ( `id` ),
  KEY data_idx ( `id`, `owner` ),
  KEY publishable ( `publishable` ),
  KEY description ( `description` )
) ENGINE = MyISAM;

#
# Table structure for table 'odm_department'
#

CREATE TABLE IF NOT EXISTS `odm_department` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_department'
#

INSERT INTO `odm_department` VALUES (NULL,'Information Systems');

#
# Table structure for table 'odm_dept_perms'
#

CREATE TABLE IF NOT EXISTS `odm_dept_perms` (
  `fid` INT(11) UNSIGNED NOT NULL,
  `dept_id` INT(11) UNSIGNED NOT NULL,
  `rights` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY fid ( `fid`, `dept_id` ),
  KEY dept_id ( `dept_id` ),
  KEY rights ( `rights` )
) ENGINE = MyISAM;

#
# Table structure for table 'odm_dept_reviewer'
#

CREATE TABLE IF NOT EXISTS `odm_dept_reviewer` (
  `dept_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY ( `dept_id`, `user_id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_dept_reviewer'
#

INSERT INTO `odm_dept_reviewer` VALUES (1,1);

#
# Table structure for table 'odm_filetypes'
#

CREATE TABLE IF NOT EXISTS `odm_filetypes` (
  `id` TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` VARCHAR(255) NOT NULL ,
  `active` TINYINT(1) NOT NULL ,
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

INSERT INTO `odm_filetypes` VALUES (NULL, 'image/gif', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'text/html', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'text/plain', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/pdf', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/pdf',1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/x-pdf', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/msword', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/jpeg', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/pjpeg', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/png', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/msexcel', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/msaccess', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'text/richtxt', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/mspowerpoint', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/octet-stream', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/x-zip-compressed', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/x-zip', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/zip', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/tiff', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/tif', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.ms-powerpoint', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.ms-excel', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.chart', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.chart-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.formula', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.formula-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.graphics', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.graphics-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.image', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.image-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.presentation', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.presentation-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.text', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.text-master', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.text-template', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'application/vnd.oasis.opendocument.text-web', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'text/csv', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'audio/mpeg', 0);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/x-dwg', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/x-dfx', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'drawing/x-dwf', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'image/svg', 1);
INSERT INTO `odm_filetypes` VALUES (NULL, 'video/3gpp', 1);

#
# Table structure for table 'odm_log'
#

CREATE TABLE IF NOT EXISTS `odm_log` (
  `id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `modified_by` VARCHAR(25) DEFAULT NULL,
  `note` TEXT,
  `revision` VARCHAR(255) NOT NULL,
  PRIMARY KEY ( `id`, `revision` ),
  KEY modified_on (`modified_on`)
) ENGINE = MyISAM;

#
# Table structure for table 'odm_odmsys'
#

CREATE TABLE IF NOT EXISTS `odm_odmsys` (
  `id` TINYINT(2) NOT NULL AUTO_INCREMENT ,
  `sys_name`  VARCHAR(16),
  `sys_value` VARCHAR(255),
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_odmsys'
#

INSERT INTO `odm_odmsys` VALUES (NULL,'version','1.3.5');

#
# Table structure for table 'odm_rights'
#

CREATE TABLE `odm_rights` (
  `RightId` TINYINT(1) NOT NULL,
  `Description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY ( `RightId` ),
  UNIQUE KEY `UnqRight` ( `Description` )
) ENGINE=MyISAM;

#
# Dumping data for table 'odm_rights'
#

INSERT INTO `odm_rights` VALUES (0,'none');
INSERT INTO `odm_rights` VALUES (1,'view');
INSERT INTO `odm_rights` VALUES (-1,'forbidden');
INSERT INTO `odm_rights` VALUES (2,'read');
INSERT INTO `odm_rights` VALUES (3,'write');
INSERT INTO `odm_rights` VALUES (4,'admin');

#
# Table structure for table 'odm_settings'
#
CREATE TABLE IF NOT EXISTS `odm_settings` (
  `id` TINYINT(2) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR( 255 ) NOT NULL ,
  `value` VARCHAR( 255 ) NOT NULL ,
  `description` VARCHAR( 255 ) NOT NULL ,
  `validation` VARCHAR( 255 ) NOT NULL ,
  PRIMARY KEY  ( `id` ) ,
  UNIQUE KEY  ( `name` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_settings'
#

INSERT INTO `odm_settings` VALUES (NULL,'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '');
INSERT INTO `odm_settings` VALUES (NULL,'title', 'Document Repository', 'This is the browser window title', 'maxsize=255');
INSERT INTO `odm_settings` VALUES (NULL,'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req');
INSERT INTO `odm_settings` VALUES (NULL,'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req');
INSERT INTO `odm_settings` VALUES (NULL,'dataDir', '/var/www/document_repository/', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255');
INSERT INTO `odm_settings` VALUES (NULL,'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255');
INSERT INTO `odm_settings` VALUES (NULL,'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255');
INSERT INTO `odm_settings` VALUES (NULL,'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num');
INSERT INTO `odm_settings` VALUES (NULL,'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'allow_signup', 'False', 'Should we display the sign-up link?', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool');
INSERT INTO `odm_settings` VALUES (NULL,'theme', 'tweeter', 'Which theme to use?', '');
INSERT INTO `odm_settings` VALUES (NULL,'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req');
INSERT INTO `odm_settings` VALUES (NULL,'base_url', 'http://localhost/opendocman', 'Set this to the url of the site. No need for trailing \"/\" here', 'url');
INSERT INTO `odm_settings` VALUES (NULL,'max_query', '500', 'Set this to the maximum number of rows you want to be returned in a file listing.', 'num');
INSERT INTO `odm_settings` VALUES (NULL,'show_footer', 'True', 'Set this to True to display the footer.', 'bool');


#
# Table structure for table 'odm_udf'
#

# User Defined Fields Table
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

CREATE TABLE IF NOT EXISTS `odm_udf` (
  `id` TINYINT(2) NOT NULL AUTO_INCREMENT ,
  `table_name` VARCHAR(50),
  `display_name` VARCHAR(16),
  `field_type` TINYINT(1),
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

#
# Table structure for table 'odm_user'
#

CREATE TABLE IF NOT EXISTS `odm_user` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(25) NOT NULL DEFAULT '',
  `password` VARCHAR(50) NOT NULL DEFAULT '',
  `department` INT(11) UNSIGNED DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `Email` VARCHAR(50) DEFAULT NULL,
  `last_name` VARCHAR(255) DEFAULT NULL,
  `first_name` VARCHAR(255) DEFAULT NULL,
  `pw_reset_code` char(32) DEFAULT NULL,
  `can_add` TINYINT(1) NULL DEFAULT 1,
  `can_checkin` TINYINT(1) NULL DEFAULT 1,
  PRIMARY KEY  ( `id` )
) ENGINE = MyISAM;

#
# Dumping data for table 'odm_user'
#

INSERT INTO `odm_user` VALUES (NULL,'admin',md5('admin'),1,'5555551212','admin@example.com','User','Admin','', 1, 1);

#
# Table structure for table 'odm_user_perms'
#

CREATE TABLE IF NOT EXISTS `odm_user_perms` (
  `fid` int(11) unsigned NOT NULL,
  `uid` int(11) unsigned NOT NULL,
  `rights` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY ( `fid`, `uid` ),
  KEY `uid` ( `uid` ),
  KEY `rights` ( `rights` )
) ENGINE=MyISAM;
