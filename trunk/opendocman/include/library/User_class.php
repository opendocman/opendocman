<?php
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
                $this->tablename= $GLOBALS['CONFIG']['table_prefix'] . 'user';
                $this->result_limit = 1; //there is only 1 user with a certain user_name or user_id
                databaseData::setTableName($GLOBALS['CONFIG']['table_prefix'] .'user');
                databaseData::databaseData($id, $connection, $database);

        }
        
        function getDeptName()
        {
                $query = "SELECT " . $GLOBALS['CONFIG']['table_prefix'] . "department.name FROM " . $GLOBALS['CONFIG']['table_prefix'] . "department, " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE " . $GLOBALS['CONFIG']['table_prefix'] . "user.id = $this->id and " . $GLOBALS['CONFIG']['table_prefix'] . "user.department=" . $GLOBALS['CONFIG']['table_prefix'] . "department.id";
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
		$query = "SELECT " . $GLOBALS['CONFIG']['table_prefix'] . "user.department FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE " . $GLOBALS['CONFIG']['table_prefix'] . "user.id = $this->id";
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
                $query = "SELECT " . $GLOBALS['CONFIG']['table_prefix'] . "data.id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "data, " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE " . $GLOBALS['CONFIG']['table_prefix'] . "data.owner = $this->id and " . $GLOBALS['CONFIG']['table_prefix'] . "user.id = " . $GLOBALS['CONFIG']['table_prefix'] . "data.owner and " . $GLOBALS['CONFIG']['table_prefix'] ."data.publishable = $publishable";
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

                $query = "SELECT admin FROM " . $GLOBALS['CONFIG']['table_prefix'] . "admin WHERE id = $this->id";
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
		$query = "SELECT * from " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer where user_id = " . $this->id;
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
	function isReviewerOfDept($dept_id)
	{
		$query = "SELECT * from " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer where user_id = " . $this->id . ' AND dept_id = ' . $dept_id;
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

	function isReviewerOfFile($fileid)
	{
		$file_obj = new FileData($fileid, $GLOBALS['connection'], $GLOBALS['database']);
		return $this->isReviewerOfDept($file_obj->getDept());
	}

	function getAllRevieweeIds() // this functions assume that you are a root thus allowing you to by pass everything
	{
		$lquery = "SELECT id FROM $this->TABLE_DATA WHERE $this->TABLE_DATA.publishable = 0";
		$lresult = mysql_query($lquery, $this->connection) or die("Error in lquery: $lquery" . mysql_error());
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
			$query = "SELECT dept_id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "dept_reviewer WHERE user_id = ".$this->id;
			$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
			$num_depts = mysql_num_rows($result);
			$query = "SELECT id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "data WHERE (";
			for($index = 0; $index < $num_depts; $index++)
			{
				list($dept) = mysql_fetch_row($result);
				if($index != $num_depts -1)
					$query = $query . " department = $dept or";
				else 
					$query = $query . " department = $dept )";
			}
			$query = $query . " and publishable = 0";
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
		$query = "SELECT id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "data WHERE publishable = '-1'";
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
		$query = "SELECT id FROM " . $GLOBALS['CONFIG']['table_prefix'] . "data WHERE publishable = '-1' and owner = ".$this->id;
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
    	$lquery = 'SELECT id FROM ' . $GLOBALS['CONFIG']['table_prefix'] . 'data WHERE status=-1 AND owner = "' . $this->id . '"';
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
    	$lquery = 'SELECT id FROM ' . $GLOBALS['CONFIG']['table_prefix'] . 'data WHERE status=-1 AND owner = "' . $this->id . '"';
    	$lresult = mysql_query($lquery) or die(mysql_error());
    	return mysql_num_rows($lresult);
    }
	function getEmailAddress()
	{
		$query = "SELECT Email FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE id=".$this->id;
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
		$query = "SELECT phone FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE id=".$this->id; 
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
		$query = "SELECT first_name, last_name FROM " . $GLOBALS['CONFIG']['table_prefix'] . "user WHERE id=".$this->id;
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
	function getPreference()
	{
		$array = array();
		global $USER_PREF;
		foreach ($USER_PREF as $key => $value)
		{
			$array[$key] = 'default';
		}
		$lquery = 'SELECT  name, value FROM ' . $GLOBALS['CONFIG']['table_prefix'] . 'user_pref WHERE owner = ' . $_SESSION['uid'];
		$lresult = mysql_query($lquery, $this->connection) or die('Error querying: ' . $lquery . '|' . mysql_error());
		$len = mysql_num_rows($lresult);
		for($i = 0; $i< $len; $i++)
		{
			list($name, $u_value) = mysql_fetch_row($lresult);
			$array[$name]=$u_value;
		}
		return $array;
	}
  }
}
