<?php
/*
User_class.php - Container for user related info
Copyright (C) 2002, 2003, 2004  Stephen Lawrence, Khoa Nguyen

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
	var $root_username;
        
        function User($id, $connection, $database)

        {
                $this->root_username = $GLOBALS['CONFIG']['root_username'];
                $this->field_name = 'username';
                $this->field_id = 'id';
                $this->tablename= $this->TABLE_USER;
                $this->result_limit = 1; //there is only 1 user with a certain user_name or user_id
                databaseData::setTableName($this->TABLE_USER);
                databaseData::databaseData($id, $connection, $database);
        }
        
        function getDeptName()
        {
                $query = "SELECT odm_department.name FROM odm_department, odm_user WHERE odm_user.id = $this->id and odm_user.department=odm_department.id";
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
	
        function getDeptId()
	{
		$query = "SELECT odm_user.department FROM odm_user WHERE odm_user.id = $this->id";
		$result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
		
		if(mysql_num_rows($result)==1)
		{
			list($department) = mysql_fetch_row($result);
			return $department;
		}
		$this->error = 'Non-unique id: ' . $this->id;
		return -1;

	}

        function getPublishedData($publishable)
        {
                $data_published = array();
                $index = 0;
                $query = "SELECT odm_data.id FROM odm_data, odm_user WHERE odm_data.owner = $this->id and odm_user.id = odm_data.owner and odm_data.publishable = $publishable";
                $result = mysql_query($query, $this->connection) or die("Error in query: ". $query .mysql_error());
                while($index<mysql_num_rows($result))
                {
                        list($data_published[$index]) = mysql_fetch_row($result);
                        $index++;
                }
                return $data_published;
        }
	
        function isAdmin()
        {
                if($this->isRoot())
                {
                        return true;
                }

                $query = "SELECT odm_admin.admin FROM odm_admin WHERE odm_admin.id = $this->id";
                $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );

                if(mysql_num_rows($result) !=1 )
                {
                	return false;
				}

                list($isadmin) = mysql_fetch_row($result);
                return $isadmin;
        }

	function isRoot()
	{
		return ($this->root_username == $this->getName());
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
		$query = "UPDATE $this->tablename SET $this->tablename.password=password('". addslashes($non_encrypted_password) ."') WHERE $this->tablename.id=$this->id";
		$result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
		return true;
	}

	function validatePassword($non_encrypted_password)
	{
		$query = "SELECT $this->tablename.username FROM $this->tablename WHERE $this->tablename.id=$this->id and password= password('". addslashes($non_encrypted_password) ."')";
		$result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
		if(mysql_num_rows($result) == 1)
                {
		        return true;
                }
		return false;
	}

	function changeName($new_name)
	{
		$query = "UPDATE $this->tablename SET $this->tablename.username='$new_name' WHERE $this->tablename.id=$this->id";
		$result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );
		return true;
	}

	function isReviewer()
	{
		$query = "SELECT * FROM odm_dept_reviewer where user_id = " . $this->id;
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
	function getAllRevieweeIds() // this functions assume that you are a root thus allowing you to by pass everything
	{
		$lquery = "SELECT id FROM $this->TABLE_DATA WHERE $this->TABLE_DATA.publishable = 0";
		$lresult = mysql_query($lquery, $this->connection) or die("Error in query: $query" . mysql_error());
		$lfile_data = array();
		$lnum_files = mysql_num_rows($lresult);
		for($lindex = 0; $lindex< $lnum_files; $lindex++)
		{
			list($lfid) = mysql_fetch_row($lresult);
			$lfile_data[$lindex] = $lfid;
		}
		return $lfile_data;
	}
	function getRevieweeIds() //return an array of files that need reviewing under this person
	{
		if($this->isReviewer())
		{
			$query = "SELECT dept_id FROM $this->TABLE_DEPT_REVIEWER WHERE user_id = ".$this->id;
			$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
			$num_depts = mysql_num_rows($result);
			$query = "SELECT id FROM odm_data WHERE (";
			for($index = 0; $index < $num_depts; $index++)
			{
				list($dept) = mysql_fetch_row($result);
				if($index != $num_depts -1)
					$query = $query . " odm_data.department = $dept or";
				else 
					$query = $query . " odm_data.department = $dept )";
			}
			$query = $query . " and odm_data.publishable = 0";
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
		$query = "SELECT id FROM $this->TABLE_DATA WHERE publishable = '-1'";
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
		$query = "SELECT id FROM odm_data WHERE publishable = '-1' and owner = ".$this->id;
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
    	$lquery = 'SELECT id FROM odm_data WHERE status=-1 AND owner = "' . $this->id . '"';
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
    	$lquery = 'SELECT id FROM odm_data WHERE status=-1 AND owner = "' . $this->id . '"';
    	$lresult = mysql_query($lquery) or die(mysql_error());
    	return mysql_num_rows($lresult);
    }
	function getEmailAddress()
	{
		$query = "SELECT Email FROM odm_user WHERE id=".$this->id;
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
		$query = "SELECT phone FROM odm_user WHERE id=".$this->id; 
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

	function getFullName()//Return full name array where array[0]=firstname and array[1]=lastname        
	{
		$query = "SELECT first_name, last_name FROM odm_user WHERE id=".$this->id;
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
  }
}
