<?php 
if( !defined('Dept_Perms_class') )
{
  define('Dept_Perms_class', 'true');
  
  class Dept_Perms
  {
	var $fid;
	var $id;
	var $rights;
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
	var $USER_PERM_TABLE = 'user_perms';
	var $DEPT_PERM_TABLE = 'dept_perms';
	var $DATA_TABLE = 'data';
	var $USER_TABLE = 'user';
	var $DEPARTMENT_TABLE = 'department';

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

	function loadData_UserPerm($right)
	{
		//Select fid, owner_id, owner_name of the file that dept-->$id has rights >= $right 
		$query = "SELECT dept_perms.fid, data.owner, user.username  FROM data, user, dept_perms WHERE (dept_perms.dept_id = $this->id  AND data.id = dept_perms.fid AND user.id = data.owner and dept_perms.rights>=$right and data.publishable = 1)";
		$result = mysql_db_query($this->database, $query, $this->connection) or die("Error in querying: $query" .mysql_error());
		$index = 0;
		$fileid_array = array();
		//$fileid_array[$index][0] ==> fid
		//$fileid_array[$index][1] ==> owner
		//$fileid_array[$index][2] ==> username
		while( $index< mysql_num_rows($result) ) 
		{
			list($fileid_array[$index][0],$fileid_array[$index][1],$fileid_array[$index][2] ) = mysql_fetch_row($result);
			$index++;	
		}
		return $fileid_array;		
	}
	function canView($data_id)
	{
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canDept($data_id, $this->VIEW_RIGHT))
				return true;
			else
				false;
		}
	}
	function canRead($data_id)
	{
		if(!$this->isForbidden($data_id))
		{
			if($this->canDept($data_id, $this->READ_RIGHT) or !$this->isPublishable($data_id) )
				return true;
			else
				false;
		}

	}
	function canWrite($data_id)
	{
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canDept($data_id, $this->WRITE_RIGHT))
				return true;
			else
				false;
		}

	}
	function canAdmin($data_id)
	{
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canDept($data_id, $this->ADMIN_RIGHT))
				return true;
			else
				false;
		}

	}
	function isForbidden($data_id)
	{
		$query = "SELECT dept_perms.rights from dept_perms WHERE dept_perms.dept_id = $this->id and dept_perms.fid = $data_id";
		$result = mysql_db_query($this->database, $query, $this->connection) or die("Error in query" .mysql_error() );
		if(mysql_num_rows($result) ==1)
		{
			list ($right) = mysql_fetch_row($result);
		if($right==$FORBIDDEN_RIGHT)
				return true;
			else
				return false;
		}
		
	}
	function isPublishable($data_id)
	{
		$user_obj = new User($data_id, $this->connection, $this->database);
		return $user_obj->isPublishable();
	}
	function canDept($data_id, $right)
	{
		$query = "Select * from dept_perms where dept_perms.dept_id = $this->id and dept_perms.fid = $data_id and dept_perms.rights>=$right";
		$result = mysql_db_query($this->database, $query, $this->connection) or die ("Error in querying: $query" .mysql_error() );
		
		switch(mysql_num_rows($result) )
		{
			case 1: return true; break;
			case 0: return false; break;
			default : $this->error = 'non-unique uid: $this->id'; break;
		}
	}
	function getPermission($data_id)
	{
	  $query = "Select dept_perms.rights from dept_perms where dept_id = $this->id and fid = $data_id";
	  $result = mysql_db_query($this->database, $query, $this->connection) or die("Error in query: .$query" . mysql_error() );
	  if(mysql_num_rows($result) == 1)
	  {
	    list($permission) = mysql_fetch_row($result);
	    return $permission;
	  }
	  else if (mysql_num_rows($result) == 0)
	    return 0;
	  else
	    return 'Non-unique error';
	}
  }//end class
}//end ifdef
?>
