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
                $this->tablename= 'user';
                $this->result_limit = 1; //there is only 1 user with a certain user_name or user_id
                databaseData::setTableName('user');
                databaseData::databaseData($id, $connection, $database);

        }
        
        function getDeptName()
        {
                $query = "SELECT department.name FROM department, user WHERE user.id = $this->id and user.department=department.id";
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
		$query = "SELECT user.department FROM user WHERE user.id = $this->id";
		$result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
		
		if(mysql_num_rows($result)==1)
		{
			list($department) = mysql_fetch_row($result);
			return $department;
		}
		$this->error = 'Non-unique uid: ' . $this->uid;
		return -1;

	}

        function getPublishedData($publishable)
        {
                $data_published = array();
                $index = 0;
                $query = "SELECT data.id, data.owner, user.username FROM data, user WHERE data.owner = $this->id and user.id = data.owner and data.publishable = $publishable";
                $result = mysql_query($query, $this->connection) or die("Error in query: ". $query .mysql_error());
                while($index<mysql_num_rows($result))
                {
                        list($data_published[$index][0], $data_published[$index][1], $data_published[$index][2]) = mysql_fetch_row($result);
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

                $query = "SELECT admin.admin FROM admin WHERE admin.id = $this->id";
                $result = mysql_query($query, $this->connection) or die("Error in querying: $query" . mysql_error() );

                if(mysql_num_rows($result) !=1 )
                {
                        header('Location:error.php?ec=14');
                        exit;
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
		$query = "SELECT * from dept_reviewer where user_id = " . $this->id;
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

        function getReviewee() //return an array of files that need reviewing
        {
                if($this->isReviewer())
                {
                        $query = "SELECT dept_id FROM dept_reviewer WHERE user_id = ".$this->id;
                        $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
                        $num_depts = mysql_num_rows($result);
                        $query = "SELECT id FROM data WHERE (";
                        for($index = 0; $index < $num_depts; $index++)
                        {
                                list($dept) = mysql_fetch_row($result);
                                if($index != $num_depts -1)
                                        $query = $query . " data.department = $dept or";
                                else 
                                        $query = $query . " data.department = $dept )";
                        }
                        $query = $query . " and data.publishable = 0";
                        mysql_free_result($result);
                        $result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
                        $file_data = array();
                        $num_files = mysql_num_rows($result);
                        for($index = 0; $index< $num_files; $index++)
                        {
                                list($fid) = mysql_fetch_row($result);
                                $file_data[$index] = new FileData($fid, $this->connection, $this->database);
                        }
                        return $file_data;				
                }		
        }

	function getRejectedFiles()
	{
		$query = "SELECT data.id FROM data WHERE publishable = '-1' and data.owner = ".$this->id;
		$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
		$file_data = array();
		$num_files = mysql_num_rows($result);
		for($index = 0; $index< $num_files; $index++)
		{
			list($fid) = mysql_fetch_row($result);
			$file_data[$index] = new FileData($fid, $this->connection, $this->database);
		}
		return $file_data;
	}
        
	function getEmailAddress()
	{
		$query = "SELECT user.Email FROM user WHERE user.id=".$this->id;
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
		$query = "SELECT user.phone FROM user WHERE user.id=".$this->id; 
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
		$query = "SELECT user.first_name, user.last_name FROM user WHERE user.id=".$this->id;
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
