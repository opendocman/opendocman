<?php
$result = mysql_query("
ALTER TABLE data
    DROP filesize
") or die("<br>Could not update" . mysql_error());
?>
