<?php
// was tinyint(4)

$result = mysql_query("ALTER TABLE data CHANGE COLUMN status status smallint(6) default NULL; # was smallint(6) unsigned default NULL") or die("<br>Could not update" . mysql_error());

$result = mysql_query("ALTER TABLE data ADD COLUMN filesize bigint(20) default NULL;") or die("<br>Could not update" . mysql_error());

$result = mysql_query("ALTER TABLE data ADD INDEX id_2 (id);") or die("<br>Could not update" . mysql_error());

$result = mysql_query("ALTER TABLE data ADD INDEX id (id);") or die("<br>Could not update" . mysql_error());

$result = mysql_query("ALTER TABLE log ADD COLUMN revision varchar(255) default NULL;") or die("<br>Could not update" . mysql_error());

?>
