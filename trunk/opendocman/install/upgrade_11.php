<?php
$result = mysql_query("
ALTER TABLE data
    ADD filesize bigint(20) NULL DEFAULT NULL AFTER reviewer_comments,
    MODIFY category tinyint(4) unsigned NOT NULL DEFAULT '0',
    MODIFY status smallint(6) NULL DEFAULT NULL,
    ADD INDEX id (id),
    ADD INDEX `id_2` (id);
#
#  Fieldformats of
#    data.category changed from smallint(5) unsigned NOT NULL DEFAULT '0' to tinyint(4) unsigned NOT NULL DEFAULT '0'.
#    data.status changed from smallint(6) unsigned NULL DEFAULT NULL to smallint(6) NULL DEFAULT NULL.
#  Possibly data modifications needed!
") or die("<br>Could not update" . mysql_error());

$result = mysql_query("
ALTER TABLE log
    ADD revision varchar(255) NULL DEFAULT NULL AFTER note;
") or die("<br>Could not update" . mysql_error());
?>
