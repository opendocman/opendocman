<?php
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
		{	return $this->loadData_UserPerm($this->VIEW_RIGHT);	}
		// return an array of user whose permission is >= none_right
		function getCurrentNoneRight()
		{	return $this->loadData_UserPerm($this->NONE_RIGHT);	}
		// return an array of user whose permission is >= read_right
		function getCurrentReadRight()
		{	return $this->loadData_UserPerm($this->READ_RIGHT);	}
		// return an array of user whose permission is >= write_right
		function getCurrentWriteRight()
		{	return $this->loadData_UserPerm($this->WRITE_RIGHT);	}
		// return an array of user whose permission is >= admin_right
		function getCurrentAdminRight()
		{	return $this->loadData_UserPerm($this->ADMIN_RIGHT);	}
		function getId()
		{	return $this->id;				}
		// All of the function above provides an abstraction for loadData_UserPerm($right)
		// If you user doesn't want to or doens't know the numeric value for permission,
		// use the function above.  LoadData_UserPerm($right) can be invoke directly.
		function loadData_UserPerm($right)
		{
			if($this->user_obj->isRoot())
				$query = "SELECT $this->TABLE_DATA.id FROM $this->TABLE_DATA WHERE 
					$this->TABLE_DATA.publishable = 1";
			else //Select fid, owner_id, owner_name of the file that user-->$id has rights >= $right 
				$query = "SELECT $this->TABLE_USER_PERMS.fid FROM $this->TABLE_DATA,
			$this->TABLE_USER_PERMS WHERE ($this->TABLE_USER_PERMS.uid = $this->id 
					AND $this->TABLE_DATA.id = $this->TABLE_USER_PERMS.fid AND 
					$this->TABLE_USER_PERMS.rights>=$right AND $this->TABLE_DATA.publishable = 1)";
			$result = mysql_query($query, $this->connection) or die("Error in querying: $query" .mysql_error());
			$index = -1;
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
					return true;
				else
					false;
			}
		}
		// return whether if this user can read $data_id
		function canRead($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			if(!$this->isForbidden($data_id) or !$filedata->i->isPublishable() )
			{
				if($this->canUser($data_id, $this->READ_RIGHT) or $this->deptperms_obj->canRead($data_id) or $this->canAdmin($data_id) )
					return true;
				else
					false;
			}

		}
		// return whether if this user can modify $data_id
		function canWrite($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
			{
				if($this->canUser($data_id, $this->WRITE_RIGHT) or $this->deptperms_obj->canWrite($data_id) or $this->canAdmin($data_id) )
					return true;
				else
					false;
			}

		}
		// return whether if this user can admin $data_id
		function canAdmin($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
			{
				if($this->canUser($data_id, $this->ADMIN_RIGHT) or $this->deptperms_obj->canAdmin($data_id) or $filedata->isOwner($this->id))
					return true;
				else
					false;
			}
		}
		// return whether if this user is forbidden to have acc
		function isForbidden($data_id)
		{
			$query = "SELECT $this->TABLE_USER_PERMS.rights FROM $this->TABLE_USER_PERMS WHERE $this->TABLE_USER_PERMS.uid = $this->id";
			$result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
			if(mysql_num_rows($result) ==1)
			{
				list ($right) = mysql_fetch_row($result);
				if($right==$this->FORBIDDEN_RIGHT)
					return true;
				else
					return false;
			}
		}
		// this all the canRead, canView, ... function provide an abstraction for this fucntion.
		// users may invoke this function if they are familiar of the numeric permision values 
		function canUser($data_id, $right)
		{
			if($this->user_obj->isRoot())
				return true;
			$query = "SELECT * FROM $this->TABLE_USER_PERMS WHERE $this->TABLE_USER_PERMS.uid = $this->id AND $this->TABLE_USER_PERMS.fid = $data_id AND $this->TABLE_USER_PERMS.rights>=$right";
			$result = mysql_query($query, $this->connection) or die ("Error in querying: $query" .mysql_error() );
			switch(mysql_num_rows($result) )
			{
				case 1: return true; break;
				case 0: return false; break;
				default : $this->error = "non-unique uid: $this->id"; break;
			}		
		}
		// return this user's permission on the file $data_id
		function getPermission($data_id)
		{
			if($GLOBALS['CONFIG']['root_username'] == $this->user_obj->getName())
				return true;

			$query = "SELECT $this->TABLE_USER_PERMS.rights FROM $this->TABLE_USER_PERMS WHERE uid = $this->id and fid = $data_id";
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
	}
}
?>
