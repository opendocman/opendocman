<?php
/*
FileData_class.php - Builds file data objects 
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.
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

if( !defined('FileData_class') )
{	
    define('FileData_class', 'true', false);

    /*
  		  mysql> describe data;
		  +-------------------+----------------------+------+-----+---------------------+----------------+
		  | id                | smallint(5) unsigned |      | PRI | NULL                | auto_increment |
		  | category          | tinyint(4) unsigned  |      |     | 0                   |                |
		  | owner             | tinyint(4) unsigned  |      |     | 0                   |                |
		  | realname          | varchar(255)         |      |     |                     |                |
		  | created           | datetime             |      |     | 0000-00-00 00:00:00 |                |
		  | description       | varchar(255)         | YES  |     | NULL                |                |
		  | comment           | varchar(255)         |      |     |                     |                |
		  | status            | tinyint(4) unsigned  |      |     | 0                   |                |
		  | department        | tinyint(4)           |      |     | 0                   |                |
		  | default_rights    | int(4)               | YES  |     | NULL                |                |
		  | publishable       | int(4)               | YES  |     | NULL                |                |
		  | reviewer          | int(4)               | YES  |     | NULL                |                |
		  | reviewer_comments | varchar(255)         | YES  |     | NULL                |                |
		  +-------------------+----------------------+------+-----+---------------------+----------------+
    */

    class FileData extends databaseData
    {
        var $category;
        var $owner;
        var $created_date;
        var $description;
        var $comment;
        var $status;
        var $department;
        var $default_rights;
        var $view_users;
        var $read_users;
        var $write_users;
        var $admin_users;
        var $filesize;
        var $isLocked;

        function FileData($id, $connection, $database)
        {
            $this->field_name = 'realname';
            $this->field_id = 'id';
            $this->result_limit = 1;  //EVERY FILE IS LISTED UNIQUELY ON THE DATABASE DATA;
            $this->tablename = $this->TABLE_DATA;
            databaseData::databaseData($id, $connection, $database);

            $this->loadData();
        }
        // exists() return a boolean whether this file exists
        function exists()
        {
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}$this->tablename WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.id = $this->id";
            $result = mysql_query($query, $this->connection);
            switch(mysql_num_rows($result))
            {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default: $this->error = 'Non-unique';
                    return $this->error;
                    break;
            }
        }
        /* loadData() is a more complex version of base class's loadData.
	This function load up all the fields in data table.*/
        function loadData()
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.category,{$GLOBALS['CONFIG']['db_prefix']}$this->tablename.owner,
                    {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.created, {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.description,
                    {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.comment, {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.status,
                    {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.department, {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.default_rights
			FROM {$GLOBALS['CONFIG']['db_prefix']}$this->tablename WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->tablename.id = '$this->id'";

            $result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
            if( mysql_num_rows($result) == $this->result_limit )
            {
                while( list($category, $owner, $created_date, $description, $comment, $status, $department, $default_rights) = mysql_fetch_row($result) )
                {
                    $this->category = $category;
                    $this->owner = $owner;
                    $this->created_date = $created_date;
                    $this->description = stripslashes($description);
                    $this->comment = stripslashes($comment);
                    $this->status = $status;
                    $this->department = $department;
                    $this->default_rights = $default_rights;
                }
            }
            else
            {
                $this->error = 'Non unique file id';
            }
            $this->isLocked = $this->status==-1;
        }

        // Update the dynamic values of the file
        function updateData()
        {
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                    category = '{$this->category}',
                    owner = '{$this->owner}',
                    description = '{$this->description}',
                    comment = '{$this->comment}',
                    status = '{$this->status}',
                    department = '{$this->department}',
                    default_rights = '{$this->default_rights}'
               WHERE
                    id = $this->id
                            ";
                    //echo $query;exit;
                             mysql_query($query) or die('Error during updateData: ' . mysql_error());
        }
        //return filesize
        function getFileSize()
        {
            return $this->filesize;
        }
        // return this file's category id
        function getCategory()
        {
            return $this->category;
        }
        function setCategory($value)
        {
            $this->category = $value;
        }
        // return this file's category name
        function getCategoryName()
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_CATEGORY.name FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_CATEGORY WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_CATEGORY.id = $this->category";
            $result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
            if( mysql_num_rows($result) == $this->result_limit)
                list($name) = mysql_fetch_row($result);
            else
            {
                $this->error = 'Non unique file id';
                return $this->error;
            }
            return $name;
        }
        // return a boolean on whether the user ID $uid is the owner of this file
        function isOwner($uid)
        {
            return ($this->getOwner()==$uid);
        }
        // return the ID of the owner of this file
        function getOwner()
        {
            return $this->owner;
        }
        // set the user_id of the file
        function setOwner($value)
        {
            $this->owner = $value;
        }
        // return the username of the owner
        function getOwnerName()
        {
            $user_obj = new User($this->owner, $this->connection, $this->database);
            return $user_obj->getName();
        }
        // return owner's full name in an array where index=0 corresponds to the last name
        // and index=1 corresponds to the first name
        function getOwnerFullName()
        {
            $user_obj = new User($this->owner, $this->connection, $this->database);
            return $user_obj->getFullName();
        }
        // return the owner's dept ID.  Often, this is also the department of the file.
        // if the owner changes his/her department after he/she changes department, then
        // the file's department will not be the same as it's owner's.
        function getOwnerDeptId()
        {
            $user_obj = new User($this->getOwner(), $this->connection, $this->database);
            return $user_obj->getDeptId();
        }
        // This function serve the same purpose as getOwnerDeptId() except that it returns
        // the department name instead of department id
        function getOwnerDeptName()
        {
            $user_obj = new User($this->getOwner(), $this->connection, $this->database);
            return $user_obj->getDeptName();
        }
        // return file description
        function getDescription()
        {
            return $this->description;
        }
        function setDescription($value)
        {
            $this->description = $value;
        }

        function getDefaultRights()
        {
            return $this->default_rights;
        }
        function setDefaultRights($value)
        {
            $this->default_rights = $value;
        }
        // return file commnents
        function getComment()
        {
            return $this->comment;
        }
        function setComment($value)
        {
            $this->comment = $value;
        }
        // return an aray of the user id of all the people who has $right right to this file
        function getUserIds($right)
        {
            $result_array = array();
            $owner_query = "SELECT owner FROM {$GLOBALS['CONFIG']['db_prefix']}$this->tablename WHERE id = $this->id";
            $u_query = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE fid = $this->id and rights >= $right";
            //query for user who has right less than $right
            $non_prev_user_query = "SELECT uid FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS WHERE fid = $this->id AND rights < $right";

            $owner_result = mysql_query($owner_query, $this->connection) or die("Error in query: ".$owner_query . mysql_error() );
            $u_result = mysql_query($u_query, $this->connection) or die("Error in query: " .$u_query . mysql_error() );
            // result of $non_prev_user_query query.  Look above for more information.
            $non_prev_u_reslt = mysql_query($non_prev_user_query, $this->connection) or die("Error in query: " .$non_prev_user_query . mysql_error() );

            $not_u_uid = array();// array of user_id that are forbidden on the file
            $d_uid = array();// init for array of dept_id;
            for($i = 0; $i<mysql_num_rows($non_prev_u_reslt); $i++)
            {
                list($not_u_uid[$i]) = mysql_fetch_row($non_prev_u_reslt);
            }

            $d_query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER.id, {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id
	  	FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS, {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER WHERE fid = $this->id AND 
                    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER.department = {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id and
                    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights >= $right";

            for($i=0; $i<sizeof($not_u_uid); $i++)
            {
                $d_query .= " and {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER.id != " . $not_u_uid[$i];
            }
            $d_result = mysql_query($d_query, $this->connection) or die("Error in query: " .$d_query . mysql_error() );
            if(sizeof($owner_result) != 1)
            {
                echo 'Error in DB, multiple ownership';
                exit;
            }
            $owner_uid = mysql_fetch_row($owner_result);
            for($i = 0; $i<mysql_num_rows($u_result); $i++)
            {
                list($u_uid[$i]) = mysql_fetch_row($u_result);
            }
            for($i = 0; $i<mysql_num_rows($d_result); $i++)
            {
                list($d_uid[$i]) = mysql_fetch_row($d_result);
            }

            if( isset($owner_uid) && isset($u_uid) )
            {
                $result_array = databaseData::combineArrays($owner_uid, $u_uid);
            }
            if( isset($result_array) && isset($d_uid) )
            {
                $result_array = databaseData::combineArrays($result_array, $d_uid);
            }

            mysql_free_result($owner_result);
            mysql_free_result($u_result);
            mysql_free_result($d_result);
            return $result_array;
        }
        // return the status of the file
        function getStatus()
        {
            return $this->status;
        }
        function setStatus($value)
        {
            mysql_query("UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA set status=$value where id = $this->id") or die(mysql_error());
        }
        // return a User OBJ of the person who checked out this file
        function getCheckerOBJ()
        {
            $user = new User($this->status, $this->connection, $this->database);
            return $user;
        }
        // return the deparment ID of the file
        function getDepartment()
        {
            return $this->department;
        }
        function setDepartment($value)
        {
            $this->department = $value;
        }
        // return the name of the deparment of the file
        function getDeptName()
        {
            $query ="SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPARTMENT WHERE id = ".$this->getDepartment().';';
            $result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
            if (mysql_num_rows($result) == 0)
            {
                echo('ERROR: No database entry exists in department table for ID = '.$this->getDepartment().'.');
                return "ERROR";
                //exit;
            }
            if (mysql_num_rows($result) > 1)
            {
                echo('ERROR: Multiple database entries exist in department table for ID = '.$this->getDepartment().'.');
                return "ERROR";
                //exit;
            }

            list($dept) = mysql_fetch_row($result);
            return $dept;
        }
        // return the date that the file was created on
        function getCreatedDate()
        {
            return $this->created_date;
        }
        // return the latest modifying date on the file
        function getModifiedDate()
        {
            /*$query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG.modified_on FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG.id = '$this->id' ORDER BY {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG.modified_on DESC LIMIT 1;";
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
                if( mysql_num_rows($result) == $this->result_limit)
                        list($name) = mysql_fetch_row($result);
                else
                {
                                $this->error = 'Non unique file id';
                                return $this->error;
                }*/

            $query = "SELECT modified_on FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG WHERE id = '$this->id' ORDER BY modified_on DESC limit 1;";
            $result = mysql_query($query) or die ("Error in query: $query. " . mysql_error());
            list($name) = mysql_fetch_row($result);
            return $name;
        }
        // return the realname of the file
        function getRealName()
        {
            return databaseData::getName();
        }
        /* getViewRightUserIds(), getReadRightUserIds(), getWriteRightUserIds(),
	getAdminRightUserIds(), getNoneRightUserIds(), provide interfaces to 
	getUserIds($right).*/
        function getViewRightUserIds()
        {
            return $this->getUserIds($this->VIEW_RIGHT);
        }

        function getReadRightUserIds()
        {
            return $this->getUserIds($this->READ_RIGHT);
        }

        function getWriteRightUserIds()
        {
            return $this->getUserIds($this->WRITE_RIGHT);
        }

        function getAdminRightUserIds()
        {
            return $this->getUserIds($this->ADMIN_RIGHT);
        }

        function getNoneRightUserIds()
        {
            return $this->getUserIds($this->NONE_RIGHT);
        }
        // return an array of user id who are forbidden to this file
        function getForbiddenRightUserIds()
        {

            $u_query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.uid FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS
	  				WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.fid = $this->id 
					AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS.rights = $this->FORBIDDEN_RIGHT";

            $d_query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER.id, {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id
	  				FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS, {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER 
					WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.fid = $this->id 
					AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER.department = {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id 
	  				AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights = $this->FORBIDDEN_RIGHT";
                                        
            $u_result = mysql_query($u_query, $this->connection) or die("Error in query: " .$u_query . mysql_error() );
            $d_result = mysql_query($d_query, $this->connection) or die("Error in query: " .$d_query . mysql_error() );
            $d_uid = array();
            $u_uid = array();
            for($i = 0; $i<mysql_num_rows($u_result); $i++)
            {
                list($u_uid[$i]) = mysql_fetch_row($u_result);
            }
            for($i = 0; $i<mysql_num_rows($d_result); $i++)
            {
                list($d_uid[$i]) = mysql_fetch_row($d_result);
            }

            $result_array = databaseData::combineArrays(array(), $u_uid);
            $result_array = databaseData::combineArrays($result_array, $d_uid);

            mysql_free_result($u_result);
            mysql_free_result($d_result);
            return $result_array;


        }
        
        // Return all depts that have forbidden for this file
        function getForbiddenRightDeptIds()
        {
            return $this->getDeptRightsIds($this->FORBIDDEN_RIGHT);
        }
        
        // Return all depts that have view for this file
        function getViewRightDeptIds()
        {
            return $this->getDeptRightsIds($this->VIEW_RIGHT);
        }
        
        // Return all depts that have read for this file
        function getReadRightDeptIds()
        {
            return $this->getDeptRightsIds($this->READ_RIGHT);
        }
        
        // Return all depts that have modify for this file
        function getModifyRightDeptIds()
        {
            return $this->getDeptRightsIds($this->WRITE_RIGHT);
        }
        
        // Return all depts that have admin for this file
        function getAdminRightDeptIds()
        {
            return $this->getDeptRightsIds($this->ADMIN_RIGHT);
        }
        // return an array of departments id who are forbidden to this file
        /*
         * getForbiddenRightDeptIds Find all departments who have forbidden perms for this file
         * @param int $right The numerical permission level
         */
        function getDeptRightsIds($right)
        {
            $did = array();
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.dept_id
                        FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS
			WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.fid = $this->id 
	  		AND {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS.rights = $right";
            $result = mysql_query($query, $this->connection) or die("Error in query: " .$query . mysql_error() );  
            
            for($i = 0; $i<mysql_num_rows($result); $i++)
            {
                list($did[$i]) = mysql_fetch_row($result);
            }
            mysql_free_result($result);
            
            return $did;
        }

        // convert a an array of user id into an array of user object
        function toUserOBJs($uid_array)
        {
            $UserOBJ_array = array();
            for($i = 0; $i<sizeof($uid_array); $i++)
            {
                $UserOBJ_array[$i] = new User($uid_array[$i], $this->connection, $this->database);
            }
            return $UserOBJ_array;
        }
        // return a boolean on whether or not this file is publisable
        function isPublishable()
        {
            $query = "SELECT publishable FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE id = '$this->id'";
            $result = mysql_query($query, $this->connection) or die('Error in query'. mysql_error());
            if(mysql_num_rows($result) != 1)
            {
                echo('DB error.  Unable to locate file id ' . $this->id . ' in table '.$GLOBALS['CONFIG']['db_prefix'].'data.  Please contact ' . $GLOBALS['CONFIG']['site_mail'] . 'for help');
                exit;
            }
            list($publishable) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $publishable;
        }
        function isArchived()
        {
            $query = "SELECT publishable FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE id = '$this->id'";
            $result = mysql_query($query, $this->connection) or die('Error in query'. mysql_error());
            if(mysql_num_rows($result) != 1)
            {
                echo('DB error.  Unable to locate file id ' . $this->id . ' in table '.$GLOBALS['CONFIG']['db_prefix'].'data.  Please contact ' . $GLOBALS['CONFIG']['site_mail'] . 'for help');
                exit;
            }
            list($publishable) = mysql_fetch_row($result);
            mysql_free_result($result);
            return ($publishable == 2);
        }
        // this function sets the publisable field in the data table to $boolean
        function Publishable($boolean = true)
        {
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA SET publishable ='$boolean', {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA.reviewer = '{$_SESSION['uid']}' WHERE id = '$this->id'";
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
        }
        // return the user id of the reviewer
        function getReviewerID()
        {
            $query = "SELECT reviewer FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE id = '$this->id'";
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            $num_hits = mysql_num_rows($result);
            if($num_hits != 1)
            {
                echo 'Multiple entry for same id(' . $this->id . ')';
                exit;
            }
            list($reviewer) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $reviewer;
        }
        // return the username of the reviewer
        function getReviewerName()
        {
            $reviewer_id = $this->getReviewerID();
            if(isset($reviewer_id))
            {
                $user_obj = new User($reviewer_id, $this->connection, $this->database);
                return $user_obj->getName();
            }
        }
        // return a user object for the reviewer
        function getReviewerOBJ()
        {
            return (new User($this->getReviewerID(), $this->connection, $this->database));
        }
        // set $comments into the reviewer comment field in the DB
        function setReviewerComments($comments)
        {
            $comments=addslashes($comments);
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA SET reviewer_comments='$comments' WHERE id='$this->id'";
            $result = mysql_query($query, $this->connection) or
                    die("Error in query: $query" . mysql_error());
        }
        // return the reviewer's comment toward this file
        function getReviewerComments()
        {
            $query = "SELECT reviewer_comments FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE id='$this->id'";
            $result = mysql_query($query, $this->connection) or
                    die("Error in query: $query" . mysql_error());
            if(mysql_num_rows($result) != 1)
            {
                echo('NON-UNIQUE entries in DB');
                exit;
            }
            list($comments) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $comments;
        }
        function temp_delete()
        {
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA SET publishable = 2 WHERE id = $this->id";
            $result = mysql_query($query, $this->connection) or
                    die("Error in query: $query" . mysql_error());
        }
        function undelete()
        {
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA SET publishable = 0 WHERE id = $this->id";
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
        }
        function isLocked()
        {
            return $this->isLocked;
        }
    }
}