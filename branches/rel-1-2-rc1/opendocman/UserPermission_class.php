<?php
if( !defined('UserPermission_class') )
{
  define('UserPermission_class', 'true', false);
  
  class UserPermission extends databaseData
  {
	var $connection;
	var $uid;
	var $database;
	var $user_obj;
	var $userperm_obj;
	var $deptperm_obj;
	var $FORBIDDEN_RIGHT;
	var $NONE_RIGHT;
	var $VIEW_RIGHT;
	var $READ_RIGHT;
	var $WRITE_RIGHT;
	var $ADMIN_RIGHT;

	function UserPermission($uid, $connection, $database)
	{
		$this->uid = $uid;
		$this->connection = $connection;
		$this->database = $database;
		$this->user_obj = new User($this->uid, $this->connection, $this->database);
		$this->userperm_obj = new User_Perms($this->user_obj->getId(), $connection, $database);
		$this->deptperm_obj = new Dept_Perms($this->user_obj->getDeptId(), $connection, $database);
		$this->FORBIDDEN_RIGHT = $this->userperm_obj->FORBIDDEN_RIGHT;
		$this->NONE_RIGHT = $this->userperm_obj->NONE_RIGHT;
		$this->VIEW_RIGHT = $this->userperm_obj->VIEW_RIGHT;
		$this->READ_RIGHT = $this->userperm_obj->READ_RIGHT;
		$this->WRITE_RIGHT = $this->userperm_obj->WRITE_RIGHT;
		$this->ADMIN_RIGHT = $this->userperm_obj->ADMIN_RIGHT;
	}
	// return an array of all the Allowed files ( right >= view_right) ID 
	function getAllowedFileIds()
	{
		$start_time = time();
		$viewable_array = $this->getViewableFileIds();
		echo '<br> <b> Load Viewable Time: ' . (time() - $start_time) . ' </b>';
		$start_time = time();
		$readable_array = $this->getReadableFileIds();
		echo '<br> <b> Load Readable Time: ' . (time() - $start_time) . ' </b>';
		$start_time = time();
		$writeable_array = $this->getWriteableFileIds();
		echo '<br> <b> Load Writable Time: ' . (time() - $start_time) . ' </b>';
		$start_time = time();
		$adminable_array = $this->getAdminableFileIds();
		echo '<br> <b> Load Admin Time: ' . (time() - $start_time) . ' </b>';
		$start_time = time();
		$result_array = array_values( array_unique( array_merge($viewable_array, $readable_array, $writeable_array, $adminable_array) ) );
		echo '<br> <b> 3 combines Time: ' . (time() - $start_time) . ' </b><br>';
		return $result_array;
	}
	// return an array of all the Allowed files ( right >= view_right) object 
	function getAllowedFileOBJs()
	{	
                return $this->convertToFileDataOBJ( $this->getAllowedFileIds() );	
    }
    // // return an array of all the Allowed files ( right >= view_right) ID     
    // One might ask why getViewableFileIds() doesn't return the combined
    // result of User_perm and Dept_Perm classes.  User_Perm_class only know
    // of it self and the same goes with Dept_Perms.  
	function getViewableFileIds()
	{	
		$array = array();
		$userperm_filearray = $this->userperm_obj->getCurrentViewOnly();
		$deptperm_filearray = $this->deptperm_obj->getCurrentViewOnly();
		
		$query = "SELECT $this->TABLE_USER_PERMS.fid FROM $this->TABLE_DATA, $this->TABLE_USER, 
			$this->TABLE_USER_PERMS WHERE ($this->TABLE_USER_PERMS.uid = $this->uid 
			AND $this->TABLE_DATA.id = $this->TABLE_USER_PERMS.fid AND 
			user.id = $this->TABLE_DATA.owner and $this->TABLE_USER_PERMS.rights < $this->VIEW_RIGHT 
			AND $this->TABLE_DATA.publishable = 1)";
		
		$result = mysql_query($query, $this->connection) or die('Unable to query: ' . $query . 'Error: ' . mysql_error());
		for($index=0; $index < mysql_num_rows($result); $index++)
		{
			list($array[$index]) = mysql_fetch_row($result);
		}
		$deptperm_filearray = removeElements($deptperm_filearray, $array); 
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
		return $result_array;
	}
	// return an array of all the Allowed files ( right >= view_right) OBJ 
	function getViewableFileOBJs()
	{	
    	return $this->convertToFileDataOBJ($this->getViewableFileIds());	
    }
	// return an array of all the Allowed files ( right >= read_right) ID 
	function getReadableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentReadRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentReadRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
		return $result_array;
	}
	//// return an array of all the Allowed files ( right >= read_right) OBJ 
	function getReadableFileOBJs()
	{	
    	return $this->convertToFileDataOBJ($this->getReadableFileIds());	
    }
	// return an array of all the Allowed files ( right >= write_right) ID 
	function getWriteableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentWriteRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentWriteRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
		return $result_array;
	}
	// return an array of all the Allowed files ( right >= write_right) ID 
	function getWriteableFileOBJs()
	{	
        return $this->convertToFileDataOBJ($this->getWriteableFileIds());	
    }
	// return an array of all the Allowed files ( right >= admin_right) ID  
	function getAdminableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentAdminRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentAdminRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = array_values( array_unique( array_merge($published_filearray, $userperm_filearray, $deptperm_filearray) ) );
		return $result_array;
	}
	// return an array of all the Allowed files ( right >= admin_right) OBJ 
	function getAdminableFileOBJs()
	{	
        return $this->convertToFileDataOBJ($this->getAdminableFileIds());	
    }
	
	function combineArrays($high_priority_array, $low_priority_array)
	{
		/*$found = false;
		$result_array = array();
		$result_array = $high_priority_array;
		$result_array_index = sizeof($high_priority_array);
		for($l = 0 ; $l<sizeof($low_priority_array); $l++)
		{
			for($r = 0; $r<sizeof($result_array); $r++)
			{
				if($result_array[$r] == $low_priority_array[$l])
				{
					$r = sizeof($result_array);
					$found = true;
				}
			}
			if(!$found)
			{
				$result_array[$result_array_index++] = $low_priority_array[$l];
			}
			$found = false;
		}*/
		//return $result_array;
		return databaseData::combineArrays($high_priority_array, $low_priority_array);
	}
	// convert an array of file id into an array of file Obj correspondent to 
	// the ids in the id array.
    function convertToFileDataOBJ($fid_array)
	{
		$filedata_array = array();
		for($i = 0; $i<sizeof($fid_array); $i++)
		{
			$filedata_array[$i] = new FileData($fid_array[$i], $this->connection, $this->database, "data");
		}
		return $filedata_array;
	}
  	// return the authority that this user have on file data_id
  	// by combining and prioritizing user and deparment right
    function getAuthority($data_id)
	{
	    if($this->user_obj->isRoot())
			return $this->ADMIN_RIGHT;
		$uperm = $this->userperm_obj->getPermission($data_id);
	    $dperm = $this->deptperm_obj->getPermission($data_id);
	    $filedata = new FileData($data_id, $this->connection, $this->database);
	    if( $filedata->isOwner($this->uid) )
	    {  return $this->ADMIN_RIGHT;	}
	    if( $uperm>=$this->userperm_obj->NONE_RIGHT and $uperm <= $this->userperm_obj->ADMIN_RIGHT)
	    {
	      if($dperm>=$this->deptperm_obj->NONE_RIGHT and $dperm <= $this->deptperm_obj->ADMIN_RIGHT)
	      {
		if($dperm>=$uperm)
		{  return $dperm;  }
		else
		{ return $uperm; }
	      }
	      return $uperm;
	    }
	}
  }
}
?>
