<?php
if ( !defined('User_Perms_class') )
{
  define('User_Perms_class', 'true', false);
  
  class User_Perms 
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
	var $USER_PERM_TABLE = "user_perms";
	var $DEPT_PERM_TABLE = "dept_perms";
	var $DATA_TABLE = "data";
	var $USER_TABLE = "user";
	var $DEPARTMENT_TABLE = "department";
	function User_Perms($id, $connection, $database)
	{
		$this->id = $id;  // this can be fid or uid
		$this->connection = $connection;
		$this->database = $database;
		$this->user_obj = new User($id, $connection, $database);
		$this->deptperms_obj = new Dept_Perms($this->user_obj->GetDeptId(), $connection, $database);
	}
	function getCurrentViewOnly()
	{	return $this->loadData_UserPerm($this->VIEW_RIGHT);	}
	function getCurrentNoneRight()
	{	return $this->loadData_UserPerm($this->NONE_RIGHT);	}
	function getCurrentReadRight()
	{	return $this->loadData_UserPerm($this->READ_RIGHT);	}
	function getCurrentWriteRight()
	{	return $this->loadData_UserPerm($this->WRITE_RIGHT);	}
	function getCurrentAdminRight()
	{	return $this->loadData_UserPerm($this->ADMIN_RIGHT);	}
	function getId()
	{	return $this->id;				}

	function loadData_UserPerm($right)
	{
		if($this->user_obj->isRoot())
			$query = "SELECT data.id, data.owner, user.username FROM data, user WHERE user.id = data.owner and data.publishable = 1";
		else //Select fid, owner_id, owner_name of the file that user-->$id has rights >= $right 
			$query = "SELECT user_perms.fid, data.owner, user.username  FROM data, user, user_perms WHERE (user_perms.uid = $this->id  AND data.id = user_perms.fid AND user.id = data.owner and user_perms.rights>=$right and data.publishable = 1)";
		$result = mysql_db_query($this->database, $query, $this->connection) or die("Error in querying: $query" .mysql_error());
		$index = 0;
		$fileid_array = array();
		//$fileid_array[$index][0] ==> fid
		//$fileid_array[$index][1] ==> owner
		//$fileid_array[$index][2] ==> username
		while($index< mysql_num_rows($result) )
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
        	if($this->canUser($data_id, $this->VIEW_RIGHT) or $this->deptperms_obj->canView($data_id)or $this->canAdmin($data_id))
            	return true;
            else
                false;
        }
    }

	function canRead($data_id)
	{
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canUser($data_id, $this->READ_RIGHT) or $this->deptperms_obj->canRead($data_id) or $this->canAdmin($data_id) )
				return true;
			else
				false;
		}

	}

	function canWrite($data_id)
	{
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canUser($data_id, $this->WRITE_RIGHT) or $this->deptperms_obj->canWrite($data_id) or $this->canAdmin($data_id) )
				return true;
			else
				false;
		}

	}

	function canAdmin($data_id)
	{
		$filedata = new FileData($data_id, $this->connection, $this->database);
		if(!$this->isForbidden($data_id) or !$this->isPublishable($data_id) )
		{
			if($this->canUser($data_id, $this->ADMIN_RIGHT) or $this->deptperms_obj->canAdmin($data_id) or $filedata->isOwner($this->id))
				return true;
			else
				false;
		}
	}

	function isForbidden($data_id)
	{
		$query = "SELECT user_perms.rights from user_perms WHERE user_perms.uid = $this->id";
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

	function canUser($data_id, $right)
	{
		if($this->user_obj->isRoot())
			return true;
		$query = "Select * from user_perms where user_perms.uid = $this->id and user_perms.fid = $data_id and user_perms.rights>=$right";
		$result = mysql_db_query($this->database, $query, $this->connection) or die ("Error in querying: $query" .mysql_error() );
		switch(mysql_num_rows($result) )
		{
			case 1: return true; break;
			case 0: return false; break;
			default : $this->error = "non-unique uid: $this->id"; break;
		}		
	}

	function getPermission($data_id)
	{
	  if($this->root_user == $this->user_obj->getName())
	  	return true;
	  $query = "Select user_perms.rights from user_perms where uid = $this->id and fid = $data_id";
	  $result = mysql_db_query($this->database, $query, $this->connection) or die("Error in query: .$query" . mysql_error() );
	  if(mysql_num_rows($result) == 1)
	  {
	    list($permission) = mysql_fetch_row($result);
	    return $permission;
	  }
	  if (mysql_num_rows($result) == 0)
	  {  
		$query = 'SELECT dept_perms.rights from dept_perms where fid = ' . $data_id . ' AND dept_id = ' . $this->user_obj->getDeptId();	
		$result = mysql_query($query, $this->connection) or die("Error in query: .$query" . mysql_error() );
		if(mysql_num_rows($result) == 1)
	  	{
	    	list($permission) = mysql_fetch_row($result);
	    	return $permission;
	  	}
	  }
	  else
	  {  return 'Non-unique error';	}
    }
  }
}
?>