<?php
/**
Category_class.php - Container for category info
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
 */
if (!defined('Category_class'))
{
    define('Category_class', 'true', false);

    class Category
    {
        /*
         * getAllCategories - Returns an array of all the categories
         * @returns array
         */

        public static function getAllCategories()
        {
            // query to get a list of available users
            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name";
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
            while ($row = mysql_fetch_assoc($result))
            {
                $categoryListArray[] = $row;
            }
            mysql_free_result ($result);
            return $categoryListArray;
        }

    }

}
