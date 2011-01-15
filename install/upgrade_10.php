<?php
/*
upgrade_10.php - For users uprading from 1.0
Copyright (C) 2002-2010  Stephen Lawrence

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
ALTER TABLE data CHANGE COLUMN comment comment varchar(255) default ''
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