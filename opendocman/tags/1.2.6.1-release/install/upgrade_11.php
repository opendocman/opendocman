<?php
/*
upgrade_11.php - For users upgrading from 1.1
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

$result = mysql_query("
ALTER TABLE `data`
    MODIFY category tinyint(4) unsigned NOT NULL DEFAULT '0',
    MODIFY status smallint(6) NULL DEFAULT NULL,
    ADD INDEX id (id),
    ADD INDEX `id_2` (id),
    ADD INDEX publishable (publishable),
    ADD INDEX description (description)
#
#  Fieldformats of
#    data.category changed from smallint(5) unsigned NOT NULL DEFAULT '0' to tinyint(4) unsigned NOT NULL DEFAULT '0'.
#    data.status changed from smallint(6) unsigned NULL DEFAULT NULL to smallint(6) NULL DEFAULT NULL.
#  Possibly data modifications needed!
#
") or die("<br>Could not update" . mysql_error());

$result = mysql_query("
ALTER TABLE `dept_perms`
    ADD INDEX rights (rights),
    ADD INDEX `dept_id` (`dept_id`),
    ADD INDEX fid (fid)
") or die("<br>Could not update dept_perms" . mysql_error());

$result = mysql_query("
ALTER TABLE log
    ADD revision varchar(255) NULL DEFAULT NULL AFTER note,
    ADD INDEX id (id),
    ADD INDEX `modified_on` (`modified_on`)
") or die("<br>Could not update log" . mysql_error());

$result = mysql_query("
ALTER TABLE `user_perms`
    ADD INDEX fid (fid),
    ADD INDEX uid (uid),
    ADD INDEX rights (rights)
") or die("<br>Could not update user_perms" . mysql_error());