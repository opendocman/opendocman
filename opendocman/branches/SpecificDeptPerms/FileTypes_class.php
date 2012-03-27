<?php
/*
FileTypes_class.php - Container for allowed file types info
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

if( !defined('FileTypes_class') )
{
    define('FileTypes_class', 'true', false);
    class FileTypes_class
    {
       /*
        * Class that handles the opendocman allowedFileTypes values
        */

       /*
        * Get value for a specific file type based on the key
        * @param string $data
        */
        function get($data)
        {

        }

        /*
         * Add a new file type
         * @param string $data
        */
        function add($data)
        {
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}filetypes (type,active) VALUES ('{$data['filetype']}','1')";
            $result = mysql_query($query) or die ('Failed to save filetypes: ' . mysql_error());
            return TRUE;
        }

        /*
        * Save all the file type info
        * @param array $data Array of values to be saved ($key,$value)
        */
        function save($data)
        {
            // First, uncheck all status values
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}filetypes SET active='0'";
            $result = mysql_query($query) or die ('Failed to un-set filetypes active values: ' . mysql_error());
            foreach ($data['types'] as $key=>$value)
            {
                //print_r($data['types']);exit;
                $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}filetypes SET active='1' WHERE id='$value'";
                //echo $query;exit;
                $result = mysql_query($query) or die ('Failed to save filetypes: ' . mysql_error());
            }
            return TRUE;
        }

        /*
        * Load active file types to an array
        * return array
        */
        function load()
        {
            $GLOBALS['CONFIG']['allowedFileTypes'] = array();
            $sql = "SELECT type FROM {$GLOBALS['CONFIG']['db_prefix']}filetypes WHERE active='1'";
            $result = mysql_query($sql) or die ('Getting filetypes failed: ' . mysql_error());
            while(list($value) = mysql_fetch_row($result))
            {
                array_push($GLOBALS['CONFIG']['allowedFileTypes'], $value);
            }

        }

        /*
         * Show the file types edit form
        */
        function edit()
        {
            $filetypes_arr = array();
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}filetypes";
            $result = mysql_query($query) or die('Failed to edit filetypes: ' . mysql_error());
            while($row = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                $filetypes_arr[] = $row;
            }

            $GLOBALS['smarty']->assign('filetypes_array',$filetypes_arr);
            display_smarty_template('filetypes.tpl');
        }

        /*
         * Show the form in order to Delete a filetype
        */
        function deleteSelect()
        {
            $filetypes_arr = array();
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}filetypes";
            $result = mysql_query($query) or die('Failed to select filetypes list: ' . mysql_error());
            while($row = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                $filetypes_arr[] = $row;
            }

            $GLOBALS['smarty']->assign('filetypes_array',$filetypes_arr);
            display_smarty_template('filetypes_deleteshow.tpl');
        }

        function delete($data)
        {
            foreach($data['types'] as $id)
            {
                $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}filetypes WHERE id={$id}";
                $result = mysql_query($query) or die('Failed to delete filetype: ' . mysql_error());
            }
            return TRUE;
        }
    }
}
