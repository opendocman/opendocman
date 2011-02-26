<?php 
/*
Dept_Perms_class.php - Dept_Perms is designed to handle permission settings of each department.
Copyright (C) 2002-2004  Stephen Lawrence, Khoa Nguyen
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

if( !defined('Dept_Perms_class') )
{
    define('Dept_Perms_class', 'true');

    class Dept_Perms extends databaseData
    {
        var $fid;
        var $id;
        var $rights;
        var $file_obj;
        var $error;
        var $chosen_mode;
        var $connection, $database;
        var $error_flag = FALSE;

        var $NONE_RIGHT = 0;
        var $VIEW_RIGHT = 1;
        var $READ_RIGHT = 2;
        var $WRITE_RIGHT = 3;
        var $ADMIN_RIGHT = 4;
        var $FORBIDDEN_RIGHT = -1;
        var $USER_MODE = 0;
        var $FILE_MODE = 1;

        function Dept_Perms($id, $connection, $database)
        {
            $this->id = $id;  // this can be fid or uid
            $this->connection = $connection;
            $this->database = $database;
        }
        function getCurrentViewOnly()
        {
            return $this->loadData_UserPerm($this->VIEW_RIGHT);
        }
        function getCurrentNoneRight()
        {
            return $this->loadData_UserPerm($this->NONE_RIGHT);
        }
        function getCurrentReadRight()
        {
            return $this->loadData_UserPerm($this->READ_RIGHT);
        }
        function getCurrentWriteRight()
        {
            return $this->loadData_UserPerm($this->WRITE_RIGHT);
        }
        function getCurrentAdminRight()
        {
            return $this->loadData_UserPerm($this->ADMIN_RIGHT);
        }
        function getId()
        {
            return $this->id;
        }
        /* loadData_userPerm($right) return a list of files that
	the department that this OBJ represents has authority >=
	than $right */
        function loadData_UserPerm($right)
        {
            //$s1 = getmicrotime();
            $fileid_array = array();
            $query = "SELECT deptperms.fid
                    FROM
                        {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as data,
                        {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS as deptperms
                    WHERE
                            deptperms.rights >= $right
                    AND
                            deptperms.dept_id=$this->id
                    AND
                            data.id=deptperms.fid
                    AND
                            data.publishable=1";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" .mysql_error());
            //$fileid_array[$index][0] ==> fid
            //$fileid_array[$index][1] ==> owner
            //$fileid_array[$index][2] ==> username
            $llen = mysql_num_rows($result);
            $index = 0;
            while($index<$llen)
            {
                list($fileid_array[$index++] ) = mysql_fetch_row($result);
            }
            return $fileid_array;
        }
        /* canView($data_id) return a boolean on whether or not this department
	has view right to the file whose ID is $data_id*/
        function canView($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            /* check  to see if this department doesn't have a forbidden right or
		if this file is publishable*/
            if(!$this->isForbidden($data_id) and $filedata->isPublishable() )
            {
                // return whether or not this deptartment can view the file
                if($this->canDept($data_id, $this->VIEW_RIGHT))
                {
                    return true;
                }
                else
                {
                    false;
                }
            }
            return false;
        }
        /* canRead($data_id) return a boolean on whether or not this department
	has read right to the file whose ID is $data_id*/
        function canRead($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            /* check  to see if this department doesn't have a forbidden right or
		if this file is publishable*/
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                // return whether or not this deptartment can read the file
                if($this->canDept($data_id, $this->READ_RIGHT) or !$filedata->isPublishable($data_id) )
                {
                    return true;
                }
                else
                {
                    false;
                }
            }
            return false;
        }
        /* canWrite($data_id) return a boolean on whether or not this department
	has modify right to the file whose ID is $data_id*/
        function canWrite($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            /* check  to see if this department doesn't have a forbidden right or
		if this file is publishable*/
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                // return whether or not this deptartment can modify the file
                if($this->canDept($data_id, $this->WRITE_RIGHT))
                {
                    return true;
                }
                else
                {
                    false;
                }
            }

        }
        /* canAdmin($data_id) return a boolean on whether or not this department
	has admin right to the file whose ID is $data_id*/
        function canAdmin($data_id)
        {
            $filedata = new FileData($data_id, $this->connection, $this->database);
            /* check  to see if this department doesn't have a forbidden right or
		if this file is publishable*/
            if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
            {
                // return whether or not this deptartment can admin the file
                if($this->canDept($data_id, $this->ADMIN_RIGHT))
                {
                    return true;
                }
                else
                {
                    false;
                }
            }

        }
        /* isForbidden($data_id) return a boolean on whether or not this department
	has forbidden right to the file whose ID is $data_id
	EX:
	$dpobj = new Dept_Perm($dept_id, $connection, $database);
	if( $dpobj.isForbidden($data_id) != $dpobj->error_code 
		and $dpobj.isForbidden($data_id) = false )
	{
		......
	} 
        */
        function isForbidden($data_id)
        {
            $this->error_flag = true; // reset flag
            $right = -1;
            $query = "SELECT $this->database.{$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights FROM $this->database.{$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id = $this->id AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.fid = $data_id";
            $result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
            if(mysql_num_rows($result) == 1)
            {
                list ($right) = mysql_fetch_row($result);
                if($right == $this->FORBIDDEN_RIGHT)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                $this->error = "Non-unique database entry found in $this->database.$this->TABLE_DEPT_PERMS";
                $this->error_flag = false;
                return 0;
            }
        }
        // canDept($data_id, $right) return a bool on whether or not this deparment has $right
        // right on file with data id of $data_id
        function canDept($data_id, $right)
        {
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id = $this->id and {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.fid = $data_id AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights >= $right";
            $result = mysql_query($query, $this->connection) or die ("Error in querying: $query" .mysql_error() );

            switch(mysql_num_rows($result) )
            {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default : $this->error = 'non-unique uid: $this->id';
                    break;
            }
        }
        // return the numeric permission setting of this department for the file with
        // ID nuber ob $data_id
        function getPermission($data_id)
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id = $this->id and {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.fid = $data_id";
            $result = mysql_query($query, $this->connection) or die("Error in query: .$query" . mysql_error() );
            if(mysql_num_rows($result) == 1)
            {
                list($permission) = mysql_fetch_row($result);
                return $permission;
            }
            else if (mysql_num_rows($result) == 0)
            {
                return 0;
            }
            else
            {
                return 'Non-unique error';
            }
        }
    }//end class
}//end ifdef