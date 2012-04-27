<?php
// was tinyint(4)
$result = mysql_query("
ALTER TABLE data CHANGE COLUMN category category smallint(5) unsigned NOT NULL default '0'
") or die("<br>Could not update" . mysql_error());
?>
