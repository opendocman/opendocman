<?php

// was int(11) default NULL
$result = mysql_query("
ALTER TABLE admin CHANGE COLUMN id id smallint(5) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was int(11) default NULL
$result = mysql_query("
ALTER TABLE admin CHANGE COLUMN admin admin tinyint(4) default NULL
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) unsigned NOT NULL auto_increment
$result = mysql_query("
ALTER TABLE category CHANGE COLUMN id id smallint(5) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());

//  was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN department department smallint(6) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was int(4) default NULL
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN reviewer reviewer smallint(6) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) unsigned NOT NULL default '0'
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN status status smallint(6) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was int(4) default NULL
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN publishable publishable tinyint(4) default NULL
") or die("<br>Could not update" . mysql_error());

// was int(4) default NULL
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN default_rights default_rights tinyint(4) default NULL
") or die("<br>Could not update" . mysql_error());

//  was text
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN comment comment varchar(255) NOT NULL default ''
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) unsigned NOT NULL auto_increment
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN id id smallint(5) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) unsigned NOT NULL default '0'
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN owner owner smallint(6) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

$result = mysql_query("
ALTER TABLE data ADD INDEX data_idx (id,owner)
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) unsigned NOT NULL auto_increment
$result = mysql_query("
ALTER TABLE department CHANGE COLUMN id id smallint(5) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE dept_perms CHANGE COLUMN fid fid smallint(5) unsigned default NULL 
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE dept_perms CHANGE COLUMN dept_id dept_id smallint(5) unsigned default NULL 
") or die("<br>Could not update" . mysql_error());

// was int(4) default NULL
$result = mysql_query("
ALTER TABLE dept_reviewer CHANGE COLUMN user_id user_id smallint(5) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was int(4) default NULL
$result = mysql_query("
ALTER TABLE dept_reviewer CHANGE COLUMN dept_id dept_id smallint(5) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was int(11) default NULL
$result = mysql_query("
ALTER TABLE log CHANGE COLUMN id id int(10) unsigned NOT NULL default '0'
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE user CHANGE COLUMN department department smallint(5) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

//  was tinyint(4) unsigned NOT NULL auto_increment
$result = mysql_query("
ALTER TABLE user CHANGE COLUMN id id smallint(5) unsigned NOT NULL auto_increment
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE user_perms CHANGE COLUMN fid fid smallint(5) unsigned default NULL
") or die("<br>Could not update" . mysql_error());

// was tinyint(4) NOT NULL default '0'
$result = mysql_query("
ALTER TABLE user_perms CHANGE COLUMN uid uid smallint(5) unsigned NOT NULL default '0'
") or die("<br>Could not update" . mysql_error());

$result = mysql_query("
ALTER TABLE user_perms ADD INDEX user_perms_idx (fid,uid,rights)
") or die("<br>Could not update" . mysql_error());

?>
