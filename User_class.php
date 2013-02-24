<?php
/*
User_class.php - Container for user related info
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

if( !defined('User_class') )
{
    define('User_class', 'true', false);

    class User extends databaseData
    {
        var $root_id;
        /**
         *
         *
         **/
        function User($id, $connection, $database)
        {
            $this->root_id = $GLOBALS['CONFIG']['root_id'];
            $this->field_name = 'username';
            $this->field_id = 'id';
            $this->tablename = $GLOBALS['CONFIG']['db_prefix'] . $this->TABLE_USER;
            $this->result_limit = 1; //there is only 1 user with a certain user_name or user_id

            databaseData::setTableName($this->TABLE_USER);
            databaseData::databaseData($id, $connection, $database);
        }

        /**
         * Return department name for current user
         * @return string
         */
        function getDeptName()
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}department.name FROM {$GLOBALS['CONFIG']['db_prefix']}department, {$GLOBALS['CONFIG']['db_prefix']}user WHERE {$GLOBALS['CONFIG']['db_prefix']}user.id = $this->id and {$GLOBALS['CONFIG']['db_prefix']}user.department={$GLOBALS['CONFIG']['db_prefix']}department.id";
            $result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
            if(mysql_num_rows($result)==1)
            {
                list($department) = mysql_fetch_row($result);
                return $department;
            }
            else
            {
                $this->error = 'Non-unique uid: ' . $this->uid;
            }
            return -1;
        }

        /**
         * Return department ID for current user
         * @return string
         */
        function getDeptId()
        {
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}user.department FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE {$GLOBALS['CONFIG']['db_prefix']}user.id = $this->id";
            $result = mysql_query($query, $this->connection) or die("Error in query".mysql_error());

            if (mysql_num_rows($result) == 1)
            {
                list($department) = mysql_fetch_row($result);
                return $department;
            }
            $this->error = 'Non-unique id: '.$this->id;
            return - 1;

        }

        /**
         * Return an array of publishable documents
         * @return array
         * @param object $publishable
         */
        function getPublishedData($publishable)
        {
            $data_published = array();
            $index = 0;
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}user WHERE {$GLOBALS['CONFIG']['db_prefix']}data.owner = $this->id and {$GLOBALS['CONFIG']['db_prefix']}user.id = {$GLOBALS['CONFIG']['db_prefix']}data.owner and {$GLOBALS['CONFIG']['db_prefix']}data.publishable = $publishable";
            $result = mysql_query($query, $this->connection) or die("Error in query: ". $query .mysql_error());
            while($index<mysql_num_rows($result))
            {
                list($data_published[$index]) = mysql_fetch_row($result);
                $index++;
            }
            return $data_published;
        }

        /**
         * Check whether user from object has Admin rights
         * @return Boolean
         */
        function isAdmin()
        {
            if ($this->isRoot())
            {
                return true;
            }
            $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}admin.admin FROM {$GLOBALS['CONFIG']['db_prefix']}admin WHERE {$GLOBALS['CONFIG']['db_prefix']}admin.id = $this->id";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
            if(mysql_num_rows($result) !=1 )
            {
                return false;
            }

            list($isadmin) = mysql_fetch_row($result);
            return $isadmin;
        }

        /**
         * Check whether user from object is root
         * @return
         */
        function isRoot()
        {
            return ($this->root_id == $this->getId());
        }

        function getPassword()
        {
            $query = "SELECT $this->tablename.password FROM $this->tablename WHERE $this->tablename.id=$this->id";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
            if(mysql_num_rows($result) !=1 )
            {
                header('Location:error.php?ec=14');
                exit;
            }
            else
            {
                list($passwd) = mysql_fetch_row($result);
                return $passwd;
            }
        }

        function changePassword($non_encrypted_password)
        {
            $query = "UPDATE $this->tablename SET $this->tablename.password=md5('". addslashes($non_encrypted_password) ."') WHERE $this->tablename.id=$this->id";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
            return true;
        }

        function validatePassword($non_encrypted_password)
        {
            $query = "SELECT $this->tablename.username FROM $this->tablename WHERE $this->tablename.id=$this->id and password= md5('". addslashes($non_encrypted_password) ."')";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
            if(mysql_num_rows($result) == 1)
            {
                return true;
            }
            else
            {
                // Check the old password() style user password
                $query = "SELECT $this->tablename.username FROM $this->tablename WHERE $this->tablename.id=$this->id and password=password('". addslashes($non_encrypted_password) ."')";
                $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
                if(mysql_num_rows($result) == 1)
                {
                    return true;
                }
            }
            return false;
        }

        function changeName($new_name)
        {
            $query = "UPDATE $this->tablename SET $this->tablename.username='$new_name' WHERE $this->tablename.id=$this->id";
            $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
            return true;
        }
        
       /*
        *   Determine if the current user is a reviewer or not
        *   @return boolean
        *
        */
        function isReviewer()
        {
            $query = "SELECT dept_id FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = " . $this->id;
            $result = mysql_query($query, $this->connection) or die('Error in query: '. $query . mysql_error());
            if(mysql_num_rows($result) > 0)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }

       /*
        *   Determine if the current user is a reviewer for a specific ID
        *   @return boolean
        *   @var string
        */
        function isReviewerForFile($file_id)
        {
            $query = "SELECT
                            d.id
                      FROM
                            {$GLOBALS['CONFIG']['db_prefix']}data as d,
                            {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer as dr
                      WHERE
                            
                            dr.dept_id = d.department AND
                            dr.user_id = {$this->id} AND
                            d.department=dr.dept_id AND
                            d.id = '$file_id'
                            ";                          
            $result = mysql_query($query, $this->connection) or die("Error in query during isReviewerForFile call: " . mysql_error());
            $num_rows = mysql_num_rows($result);
            if($num_rows < 1)
            {
                return false;
            }
            return true;
        }
        
        // this functions assume that you are an admin thus allowing you to review all departments
        function getAllRevieweeIds() 
        {
            if($this->isAdmin())
            {
                $lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA.publishable = 0";
                $lresult = mysql_query($lquery, $this->connection) or die("Error in query: $lquery" . mysql_error());
                $lfile_data = array();
                $lnum_files = mysql_num_rows($lresult);
                for($lindex = 0; $lindex< $lnum_files; $lindex++)
                {
                    list($lfid) = mysql_fetch_row($lresult);
                    $lfile_data[$lindex] = $lfid;
                }
                return $lfile_data;
            }
        }
        
        /*
         * getRevieweeIds - Return an array of files that need reviewing under this person
         * @return array
         */
        function getRevieweeIds() 
        {
            if($this->isReviewer())
            {
                // Which departments can this user review?
                $query = "SELECT dept_id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_REVIEWER WHERE user_id = ".$this->id;
                $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
                
                // How many reviewable departments are there?
                $num_depts = mysql_num_rows($result);
                
                // Build the query
                $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE (";
                for($index = 0; $index < $num_depts; $index++)
                {
                    list($dept) = mysql_fetch_row($result);
                    if($index != $num_depts -1)
                    {
                        $query = $query . " {$GLOBALS['CONFIG']['db_prefix']}data.department = $dept or";
                    }
                    else
                    {
                        $query = $query . " {$GLOBALS['CONFIG']['db_prefix']}data.department = $dept )";
                    }
                }
                $query = $query . " and {$GLOBALS['CONFIG']['db_prefix']}data.publishable = 0";

                mysql_free_result($result);
                $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
                $file_data = array();
                $num_files = mysql_num_rows($result);
                for($index = 0; $index< $num_files; $index++)
                {
                    list($fid) = mysql_fetch_row($result);
                    $file_data[$index] = $fid;
                }
                return $file_data;
            }
        }
        
        function getAllRejectedFileIds()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE publishable = '-1'";
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            $file_data = array();
            $num_files = mysql_num_rows($result);
            for($index = 0; $index< $num_files; $index++)
            {
                list($fid) = mysql_fetch_row($result);
                $file_data[$index] = $fid;
            }
            return $file_data;
        }
        
        function getRejectedFileIds()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE publishable = '-1' and owner = '" . $this->id . "'";
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            $file_data = array();
            $num_files = mysql_num_rows($result);
            for($index = 0; $index< $num_files; $index++)
            {
                list($fid) = mysql_fetch_row($result);
                $file_data[$index] = $fid;
            }
            return $file_data;
        }
        
        function getExpiredFileIds()
        {
            $lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status=-1 AND owner = '$this->id'";
            $lresult = mysql_query($lquery) or die(mysql_error());
            $llen = mysql_num_rows($lresult);
            $file_data = array();
            for($index = 0; $index< $llen; $index++)
            {
                list($fid) = mysql_fetch_row($lresult);
                $file_data[$index] = $fid;
            }
            return $file_data;
        }
        
        function getNumExpiredFiles()
        {
            $lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status=-1 AND owner = '$this->id'";
            $lresult = mysql_query($lquery) or die(mysql_error());
            return mysql_num_rows($lresult);
        }
        
        function getEmailAddress()
        {
            $query = "SELECT Email FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=".$this->id;
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            if(mysql_num_rows($result) > 1)
            {
                echo('Non-unique key DB error');
                exit;
            }
            list($email) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $email;
        }

        function getPhoneNumber()
        {
            $query = "SELECT phone FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=".$this->id;
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            if(mysql_num_rows($result) > 1)
            {
                echo('Non-unique key DB error');
                exit;
            }
            list($phone) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $phone;
        }
        
        //Return full name array where array[0]=firstname and array[1]=lastname
        function getFullName()
        {
            $query = "SELECT first_name, last_name FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id=".$this->id;
            $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
            if(mysql_num_rows($result) > 1)
            {
                echo('Non-unique key DB error');
                exit;
            }
            list($full_name[0], $full_name[1]) = mysql_fetch_row($result);
            mysql_free_result($result);
            return $full_name;
        }

        //Return list of checked out files to root
        function getCheckedOutFiles()
        {
            if ($this->isRoot())
            {
                $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status>0";
                $result = mysql_query($query) or die("Error trying to create checked out files list: $lquery" . mysql_error());
                $llen = mysql_num_rows($result);
                $file_data = array();
                for ($index = 0; $index < $llen; $index++)
                {
                    list($fid) = mysql_fetch_row($result);
                    $file_data[$index] = $fid;
                }
                return $file_data;
            }
        }
        
        /*
         * getAllUsers - Returns an array of all the active users
         * @returns array
         */
        public static function getAllUsers()
        {
                // query to get a list of available users
                $query = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
                $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
                while($row = mysql_fetch_assoc($result)){
                    $userListArray[] = $row;
                }
                return $userListArray;
        }

    }
}