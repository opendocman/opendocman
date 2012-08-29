<?php

/**
 * Description of AccessLog_class.php
 *
 * This class provides the ability to track various changes to a file by
 * utilizing an access log
 *
    Copyright (C) 2012 Stephen Lawrence Jr.

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
 *
 * @author Stephen Lawrence Jr.
 * @param string $accesslog
 */
class AccessLog extends Plugin {
    
    var $accesslog='';
    
    /*
     * AccessLog constructor for the AccessLog plugin
     * @param string $_AccessLog Message to display
     */
    function AccessLog($_accesslog='') {
        $this->name = 'AccessLog';
        $this->author = 'Stephen Lawrence Jr';
        $this->version = '1.0';
        $this->homepage = 'http://www.opendocman.com';
        $this->description = 'AccessLog Plugin for OpenDocMan';
        
        $this->accesslog = $_accesslog;
    }

    /*
     * @param string $_var The string to display
     */
    function setAccessLog($_var) {
        $this->accesslog = $_var;
    }

    /*
     * @returns string $var Get the value of accesslog var
     */
    function getAccessLog() {
        $var = $this->accesslog;
        return $var;
    }
    
    /*
     * Draw the admin menu
     * Required if you want an admin menu to show for your plugin
     */
    function onAdminMenu()
    {
        $curdir = dirname(__FILE__);
        $GLOBALS['smarty']->display('file:' . $curdir . '/templates/accesslog.tpl');
    }

    /*
     * Create the entry into the access_log database
     * @param int $fileId
     * @param string $type The type of entry to describe what happened
     */
    static function addLogEntry($fileId, $type)
    {
        if ($fileId == 0)
        {
            global $id;
            $fileId = $id;
        }
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}access_log (file_id,user_id,timestamp,action) VALUES ( '$fileId', '$_SESSION[uid]',NOW(), '$type')";
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    }

}