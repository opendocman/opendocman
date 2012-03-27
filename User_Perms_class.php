<?php
/*
User_Perms_class.php - relates users to specific files
Copyright (C) 2002-2012 Stephen Lawrence Jr.

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

if ( !defined('User_Perms_class') )
{
    define('User_Perms_class', 'true', false);

    class User_Perms extends databaseData
    {
        var $fid;
        var $id;
        var $rights;
        var $user_obj;
        var $deptperms_obj;
        var $file_obj;
        var $error;
        var $chosen_mode;
        var $connection, $database;

        var $NONE_RIGHT = 0;
        var $VIEW_RIGHT = 1;
        var $READ_RIGHT = 2;
        var $WRITE_RIGHT = 3;
        var $ADMIN_RIGHT = 4;
        var $FORBIDDEN_RIGHT = -1;
        var $USER_MODE = 0;
        var $FILE_MODE = 1;
        function User_Perms($id, $connection, $database)
        {
            $this->id = $id;  // this can be fid or uid
            $this->connection = $connection;
            $this->database = $database;
            $this->user_obj = new User($id, $connection, $database);
            $this->deptperms_obj = new Dept_Perms($this->user_obj->GetDeptId(), $connection, $database);
        }
        // return an array of user whose permission is >= view_right
        function getCurrentViewOnly()
        {
            return $this->loadData_UserPerm($this->VIEW_RIGHT);
        }
        // return an array of user whose permission is >= none_right
        function getCurrentNoneRight()
        {
            return $this->loadData_UserPerm($this->NONE_RIGHT);
        }
        // return an array of user whose permission is >= read_right
        function getCurrentReadRight()
        {
            return $this->loadData_UserPerm($this->READ_RIGHT);
        }
        // return an array of user whose permission is >= write_right
        function getCurrentWriteRight()
        {
            return $this->loadData_UserPerm($this->WRITE_RIGHT);
        }
        // return an array of user whose permission is >= admin_right
        function getCurrentAdminRight()
        {
            return $this->loadData_UserPerm($this->ADMIN_RIGHT);
        }
        function getId()
        {
            return $this->id;
        }

        /*
         * All of the functions above provide an abstraction for loadData_UserPerm($right).
         * If your user doesn't want to or does not know the numeric value for permission,
         * use the function above.  LoadData_UserPerm($right) can be invoke directly.
         * @param integer $right The "Right" that is bein checked.
         */
        function loadData_UserPerm($right)
        {
            if($this->user_obj->isAdmin())
            {
                $query = "SELECT d.id
                        FROM
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d
                        WHERE
                            d.publishable = 1";
            }
            elseif ($this->user_obj->isReviewer())
            {
                // If they are a reviewer, let them see files in all departments they are a reviewer for
                $query = "SELECT d.id
                        FROM
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d,
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_REVIEWER as dr
                        WHERE
                            d.publishable = 1
                        AND
                            dr.dept_id = d.department
                        AND
                            dr.user_id = $this->id";
            }
            else
            {
                //Select fid, owner_id, owner_name of the file that user-->$id has rights >= $right
                $query = "SELECT up.fid
                        FROM
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d,
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS as up
                        WHERE (
                                    up.uid = $this->id
				AND 
                                    d.id = up.fid
                                AND
                                    up.rights>=$right
                                AND
                                    d.publishable = 1
                              )";
            }
            //$start = getmicrotime();
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" .mysql_error());
            $index = 0;
            $fileid_array = array();
            //$fileid_array[$index][0] ==> fid
            //$fileid_array[$index][1] ==> owner
            //$fileid_array[$index][2] ==> username
            $llen = mysql_num_rows($result);
            while($index< $llen )
            {
                list($fileid_array[++$index] ) = mysql_fetch_row($result);
            }
            return $fileid_array;
        }
        // return whether if this user can view $data_id
        function canView($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                if($this->canUser($data_id, $this->VIEW_RIGHT) or $this->deptperms_obj->canView($data_id)or $this->canAdmin($data_id))
                {
                    return true;
                }
                else
                {
                    false;
                }
            }
        }
        // return whether if this user can read $data_id
        function canRead($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            if(!$this->isForbidden($data_id) or !$filedata->i->isPublishable() )
            {
                if($this->canUser($data_id, $this->READ_RIGHT) or $this->deptperms_obj->canRead($data_id) or $this->canAdmin($data_id) )
                {
                    return true;
                }
                else
                {
                    false;
                }
            }

        }
        // return whether if this user can modify $data_id
        function canWrite($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                if($this->canUser($data_id, $this->WRITE_RIGHT) or $this->deptperms_obj->canWrite($data_id) or $this->canAdmin($data_id) )
                {
                    return true;
                }
                else
                {
                    false;
                }
            }

        }
        // return whether if this user can admin $data_id
        function canAdmin($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                if($this->canUser($data_id, $this->ADMIN_RIGHT) or $this->deptperms_obj->canAdmin($data_id) or $filedata->isOwner($this->id))
                {
                    return true;
                }
                else
                {
                    false;
                }
            }
        }
        // return whether if this user is forbidden to have acc
        function isForbidden($data_id)
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.rights FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.uid = $this->id";
            $result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
            if(mysql_num_rows($result) ==1)
            {
                list ($right) = mysql_fetch_row($result);
                if($right==$this->FORBIDDEN_RIGHT)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        
        /*
         * This function is used by all the canRead, canView, etc... abstract functions.
         * Users may invoke this function directly if they are familiar of the numeric permision values.
         * If they are an "Admin" or "Reviewer" for this file return true right away
         * @param integer $data_id The ID number of the file in question
         * @param integer $right The number of the "right" ID that is being checked
         * @return true They CAN perform the right
         */
        function canUser($data_id, $right)
        {
            if($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id))
            {
                return true;
            }
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.uid = $this->id AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.fid = $data_id AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.rights>=$right";
            $result = mysql_query($query, $this->connection) or die ("Error in querying: $query" .mysql_error() );
            switch(mysql_num_rows($result) )
            {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default : $this->error = "non-unique uid: $this->id";
                    break;
            }
        }
        // return this user's permission on the file $data_id
        function getPermission($data_id)
        {
            if($GLOBALS['CONFIG']['root_id'] == $this->user_obj->getId())
            {
                return true;
            }

            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.rights FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE uid = $this->id and fid = $data_id";
            $result = mysql_query($query, $this->connection) or die("Error in query: .$query" . mysql_error() );
            if(mysql_num_rows($result) == 1)
            {
                list($permission) = mysql_fetch_row($result);
                return $permission;
            }
            elseif (mysql_num_rows($result) == 0)
            {
                return -999;
            }
        }
        
        /*
         * getAllRights - Returns an array of all the available rights values
         * @returns array
         */

        public static function getAllRights()
        {
            // query to get a list of available users
            $query = "SELECT RightId, Description FROM {$GLOBALS['CONFIG']['db_prefix']}rights order by RightId";
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in querry: $query. " . mysql_error());
            while ($row = mysql_fetch_assoc($result))
            {
                $rightsListArray[] = $row;
            }
            mysql_free_result($result);
            return $rightsListArray;
        }

    }
}