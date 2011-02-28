<?php
/*
Settings_class.php - Container for settings related info
Copyright (C) 2010-2011 Stephen Lawrence Jr.

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

if( !defined('Settings_class') )
{
    define('Settings_class', 'true', false);

   /*
    * Class that handles the opendocman settings values
    */

    /**
     * Description of Settings_class
     *
     * @author Stephen J. Lawrence Jr.
     */
    class Settings
    {
       /*
        * Get value for a specific setting based on the key
        * @param string $key
        */
        function get($key)
        {

        }
       /*
        * Save all the settings
        * @param array $settings Array of values to be saved ($key,$value)
        */
        function save($data)
        {
            foreach ($data as $key=>$value)
            {
                $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}settings SET value='$value' WHERE name='$key'";
                $result = mysql_query($query) or die ('Failed to save settings: ' . mysql_error());
            }
            return TRUE;
        }
        /*
        * Load settings to an array
        * return array
        */
        function load()
        {
            $sql = "SELECT name,value FROM {$GLOBALS['CONFIG']['db_prefix']}settings";
            $result = mysql_query($sql) or die ('Getting settings failed: ' . mysql_error());
            while(list($key, $value) = mysql_fetch_row($result))
            {
                $GLOBALS['CONFIG'][$key] = $value;
            }

        }

        /*
         * Show the settings edit form
        */
        function edit()
        {
            $settings_arr = array();
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}settings";
            $result = mysql_query($query) or die('Failed to edit settings: ' . mysql_error());
            while($row = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                $settings_arr[] = $row;
            }

            $GLOBALS['smarty']->assign('themes', $this->getThemes());
            $GLOBALS['smarty']->assign('languages', $this->getLanguages());
            $GLOBALS['smarty']->assign('useridnums', $this->getUserIdNums());
            $GLOBALS['smarty']->assign('settings_array',$settings_arr);
            display_smarty_template('settings.tpl');
        }
        /*
        * Validate a specific setting based on its validation type
        * @param string $key The name of the setting to be tested
        * @param string $value The value of the setting to be tested
        */
        function validate($data,$value)
        {
            // NOT IMPLEMENTED
        }
        /*
         * This function will return an array of the possible theme names found in the /templates folder
         * for use in the settings form
        */
        function getThemes()
        {
            $themes = $this->getFolders( ABSPATH . 'templates');
            return $themes;
        }

        function getLanguages()
        {
            $languages = $this->getFolders( ABSPATH . 'includes/language');
            return str_replace('.php','',$languages);
        }

        function getFolders($path = '.')
        {
            $file_list=array();
            if ($handle = opendir($path))
            {
                while (false !== ($file = readdir($handle)))
                {
                    // Filter out any other types of folders that might be in here
                    if ($file != "." && $file != ".." && $file != ".svn" && $file != 'README' && $file != 'sync.sh' && $file != 'common')
                    {
                        array_push($file_list, $file);
                    }
                }
                closedir($handle);
            }
            return $file_list;
        }

        /*
         * Return an array of user names
         */
        function getUserIdNums()
        {
            $query = "SELECT id,username from {$GLOBALS['CONFIG']['db_prefix']}user";
            $result = mysql_query($query) or die('Failed to read user names for settings: ' . mysql_error());
            $useridnums_arr = array();
            while($row = mysql_fetch_array($result))
            {
                array_push($useridnums_arr,$row);
            }
            return $useridnums_arr;
        }

    }
}
