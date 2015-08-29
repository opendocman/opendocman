<?php
/*
classHeaders.php - loads common classes
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.
 * 
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

if (!defined('classHeader')) {
    define('classHeader', 'true', false);
    include_once('databaseData_class.php');
    include_once('User_class.php');
    include_once('Department_class.php');
    include_once('User_Perms_class.php');
    include_once('FileData_class.php');
    include_once('Department_class.php');
    include_once('Dept_Perms_class.php');
    include_once('UserPermission_class.php');

    /*
     * @param $hi_priority_array array 
     * @param $hi_postfix 
     * @param $low_priority_array
     * @param $low_postfix
     */
    function advanceCombineArrays($hi_priority_array, $hi_postfix, $low_priority_array, $low_postfix)
    {
        //merge higher priority onto lower priority one.
        $user_rights = array();
        $k = 0;
        $foundFlag = false;
        //create a multidimension array: element of view and right of view
        for ($i = 0; $i<sizeof($low_priority_array); $i++) {
            $user_rights[$i] = array($low_priority_array[$i], $low_postfix);
        }

        $k = sizeof($user_rights);
        for ($m = 0; $m<sizeof($hi_priority_array); $m++) {
            for ($u = 0; $u<sizeof($user_rights); $u++) {
                if ($user_rights[$u][0] == $hi_priority_array[$m] and $hi_postfix!='NULL') {
                    $user_rights[$u][1] = $hi_postfix;
                    $foundFlag = true;
                }
                if ($user_rights[$u][0] == $hi_priority_array[$m][0] and $hi_postfix =='NULL') {
                    $user_rights[$u][1] = $hi_priority_array[$m][1];
                    $foundFlag = true;
                }
            }
            if ($foundFlag==false & $hi_postfix != 'NULL') {
                $user_rights[$k++]= array($hi_priority_array[$m], $hi_postfix);
            }
            if ($foundFlag==false & $hi_postfix == 'NULL') {
                $user_rights[$k++]= $hi_priority_array[$m];
            }
            $foundFlag = false;
        }
        return $user_rights;
    }
    function combineArrays($high_priority_array, $low_priority_array)
    {
        $found = false;
        $result_array = array();
        $result_array = $high_priority_array;
        $result_array_index = sizeof($high_priority_array);
        for ($l = 0 ; $l<sizeof($low_priority_array); $l++) {
            for ($r = 0; $r<sizeof($result_array); $r++) {
                if ($result_array[$r] == $low_priority_array[$l] && $high_priority_array[$r] == true) {
                    $r = sizeof($result_array);
                    $found = true;
                }
            }
            if (!$found) {
                $result_array[$result_array_index++] = $low_priority_array[$l];
            }
            $found = false;
        }
        return $result_array;
    }
}
