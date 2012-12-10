<?php
/*
UserPermission_class.php - relates users to files 
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
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

if( !defined('UserPermission_class') )
{
    define('UserPermission_class', 'true', false);

    class UserPermission extends databaseData
    {
        var $connection;
        var $uid;
        var $database;
        var $user_obj;
        var $userperm_obj;
        var $deptperm_obj;
        var $FORBIDDEN_RIGHT;
        var $NONE_RIGHT;
        var $VIEW_RIGHT;
        var $READ_RIGHT;
        var $WRITE_RIGHT;
        var $ADMIN_RIGHT;

        function UserPermission($uid, $connection, $database)
        {
            $this->uid = $uid;
            $this->connection = $connection;
            $this->database = $database;
            $this->user_obj = new User($this->uid, $this->connection, $this->database);
            $this->userperm_obj = new User_Perms($this->user_obj->getId(), $connection, $database);
            $this->deptperm_obj = new Dept_Perms($this->user_obj->getDeptId(), $connection, $database);
            $this->FORBIDDEN_RIGHT = $this->userperm_obj->FORBIDDEN_RIGHT;
            $this->NONE_RIGHT = $this->userperm_obj->NONE_RIGHT;
            $this->VIEW_RIGHT = $this->userperm_obj->VIEW_RIGHT;
            $this->READ_RIGHT = $this->userperm_obj->READ_RIGHT;
            $this->WRITE_RIGHT = $this->userperm_obj->WRITE_RIGHT;
            $this->ADMIN_RIGHT = $this->userperm_obj->ADMIN_RIGHT;
        }
        // return an array of all the Allowed files ( right >= view_right) ID
        function getAllowedFileIds()
        {
            $viewable_array = $this->getViewableFileIds();
            $readable_array = $this->getReadableFileIds();
            $writeable_array = $this->getWriteableFileIds();
            $adminable_array = $this->getAdminableFileIds();
            $result_array = array_values( array_unique( array_merge($viewable_array, $readable_array, $writeable_array, $adminable_array) ) );
            return $result_array;
        }
        // return an array of all the Allowed files ( right >= view_right) object
        function getAllowedFileOBJs()
        {
            return $this->convertToFileDataOBJ( $this->getAllowedFileIds() );
        }
        // // return an array of all the Allowed files ( right >= view_right) ID
        // One might ask why getViewableFileIds() doesn't return the combined
        // result of User_perm and Dept_Perm classes.  User_Perm_class only know
        // of it self and the same goes with Dept_Perms.
        /*function getViewableFileIds2()
	{	
		$array = array();
		//These 2 below takes half of the execution time for this function
		$userperm_filearray = ($this->userperm_obj->getCurrentViewOnly());
		$deptperm_filearray = ($this->deptperm_obj->getCurrentViewOnly());
		$query = "SELECT $this->TABLE_USER_PERMS.fid FROM $this->TABLE_DATA, 
			$this->TABLE_USER_PERMS WHERE ($this->TABLE_USER_PERMS.uid = $this->uid 
			AND $this->TABLE_DATA.id = $this->TABLE_USER_PERMS.fid 
			AND $this->TABLE_USER_PERMS.rights < $this->VIEW_RIGHT 
			AND $this->TABLE_DATA.publishable = 1)";
		$result = mysql_query($query, $this->connection) or die('Unable to query: ' . $query . 'Error: ' . mysql_error());
		$len = mysql_num_rows($result);
		for($index=0; $index < $len; $index++)
		{
			list($array[$index]) = mysql_fetch_row($result);
		}
		$deptperm_filearray = array_diff($deptperm_filearray, $array); 
		$total_listing = array_merge($userperm_filearray , $deptperm_filearray);
		$total_listing = array_unique( $total_listing);
		$result_array = array_values($total_listing);
		return $result_array;
	}*/
        function getViewableFileIds()
        {
            $array = array();
            //These 2 below takes half of the execution time for this function
            $userperm_filearray = ($this->userperm_obj->getCurrentViewOnly());
            $deptperm_filearray = ($this->deptperm_obj->getCurrentViewOnly());
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.fid FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA,
                    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE ({$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.uid = $this->uid
				AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA.id = {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.fid
				AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.rights < $this->VIEW_RIGHT
				AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA.publishable = 1)";
            $result = mysql_query($query, $this->connection) or die('Unable to query: ' . $query .
                    'Error: ' . mysql_error());
            $len = mysql_num_rows($result);
            for($index=0; $index < $len; $index++)
            {
                list($array[$index]) = mysql_fetch_row($result);
            }
            $deptperm_filearray = array_diff($deptperm_filearray, $array);
            $deptperm_filearray = array_diff($deptperm_filearray, $userperm_filearray);
            $total_listing = array_merge($userperm_filearray , $deptperm_filearray);
            //$total_listing = array_unique( $total_listing);
            //$result_array = array_values($total_listing);
            return $total_listing;
        }
// return an array of all the Allowed files ( right >= view_right) OBJ 
        function getViewableFileOBJs()
        {
            return $this->convertToFileDataOBJ($this->getViewableFileIds());
        }
        // return an array of all the Allowed files ( right >= read_right) ID
        function getReadableFileIds()
        {
            $userperm_filearray = $this->userperm_obj->getCurrentReadRight();
            $deptperm_filearray = $this->deptperm_obj->getCurrentReadRight();
            $published_filearray = $this->user_obj->getPublishedData(1);
            $result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
            return $result_array;
        }
        //// return an array of all the Allowed files ( right >= read_right) OBJ
        function getReadableFileOBJs()
        {
            return $this->convertToFileDataOBJ($this->getReadableFileIds());
        }
        // return an array of all the Allowed files ( right >= write_right) ID
        function getWriteableFileIds()
        {
            $userperm_filearray = $this->userperm_obj->getCurrentWriteRight();
            $deptperm_filearray = $this->deptperm_obj->getCurrentWriteRight();
            $published_filearray = $this->user_obj->getPublishedData(1);
            $result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
            return $result_array;
        }
        // return an array of all the Allowed files ( right >= write_right) ID
        function getWriteableFileOBJs()
        {
            return $this->convertToFileDataOBJ($this->getWriteableFileIds());
        }
        // return an array of all the Allowed files ( right >= admin_right) ID
        function getAdminableFileIds()
        {
            $userperm_filearray = $this->userperm_obj->getCurrentAdminRight();
            $deptperm_filearray = $this->deptperm_obj->getCurrentAdminRight();
            $published_filearray = $this->user_obj->getPublishedData(1);
            $result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
            return $result_array;
        }
        // return an array of all the Allowed files ( right >= admin_right) OBJ
        function getAdminableFileOBJs()
        {
            return $this->convertToFileDataOBJ($this->getAdminableFileIds());
        }

        function combineArrays($high_priority_array, $low_priority_array)
        {
            /*$found = false;
		$result_array = array();
		$result_array = $high_priority_array;
		$result_array_index = sizeof($high_priority_array);
		for($l = 0 ; $l<sizeof($low_priority_array); $l++)
		{
			for($r = 0; $r<sizeof($result_array); $r++)
			{
				if($result_array[$r] == $low_priority_array[$l])
				{
					$r = sizeof($result_array);
					$found = true;
				}
			}
			if(!$found)
			{
				$result_array[$result_array_index++] = $low_priority_array[$l];
			}
			$found = false;
		}*/
            //return $result_array;
            return databaseData::combineArrays($high_priority_array, $low_priority_array);
        }
        // convert an array of file id into an array of file Obj correspondent to
        // the ids in the id array.
        /*function convertToFileDataOBJ($fid_array)
	{
		$filedata_array = array();
		for($i = 0; $i<sizeof($fid_array); $i++)
		{
			$filedata_array[$i] = new FileData($fid_array[$i], $this->connection, $this->database, "data");
		}
		return $filedata_array;
	}*/

        /*
         * getAuthority
         * Return the authority that this user have on file data_id
         * by combining and prioritizing user and deparment right
         * @param $data_id int
         * @param $file_obj object current file object
         */
        function getAuthority($data_id)
        {
            $data_id = (int) $data_id;
            $fileData = new FileData($data_id, $GLOBALS['connection'], DB_NAME);
            if ($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id))
            {
                return $this->ADMIN_RIGHT;
            }
            if ($fileData->isOwner($this->uid) && $fileData->isLocked())
            {
                return $this->WRITE_RIGHT;
            }
            $uperm = $this->userperm_obj->getPermission($data_id);
            $dperm = $this->deptperm_obj->getPermission($data_id);
            if ($uperm >= $this->userperm_obj->NONE_RIGHT and $uperm <= $this->userperm_obj->ADMIN_RIGHT)
            {
                return $uperm;
            } else
            {
                return $dperm;
            }
        }

    }

}