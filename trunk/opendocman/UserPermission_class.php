<?php
if( !defined('UserPermission_class') )
{
  define('UserPermission_class', 'true', false);
  
  class UserPermission
  {
	var $connection;
	var $uid;
	var $database;
	var $user_obj;
	var $userperm_obj;
	var $deptperm_obj;
	var $FORBIDDEN_RIGHT;
	var $NON_RIGHT;
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
		$this->NONE_RIGHT = $this->userperm_obj->NONE_RIGHE;
		$this->VIEW_RIGHT = $this->userperm_obj->VIEW_RIGHT;
		$this->READ_RIGHT = $this->userperm_obj->READ_RIGHT;
		$this->WRITE_RIGHT = $this->userperm_obj->WRITE_RIGHT;
		$this->ADMIN_RIGHT = $this->userperm_obj->ADMIN_RIGHT;
	}
	function getAllowedFileIds()
	{
		$viewable_array = $this->getViewableFileIds();
		$readable_array = $this->getReadableFileIds();
		$writeable_array = $this->getWriteableFileIds();
		$adminable_array = $this->getAdminableFileIds();
		$result_array = $this->combineArrays($adminable_array, $writeable_array);
		$ressult_array = $this->combineArrays($result_array, $readable_array);
		$result_array = $this->combinearrays($result_array, $viewable_array);
		return $result_array;
	}

	function getAllowedFileOBJs()
	{	
                return $this->convertToFileDataOBJ( $this->getAllowedFileIds() );	
        }
        
	function getViewableFileIds()
	{	
		$userperm_filearray = $this->userperm_obj->getCurrentViewOnly();
		$deptperm_filearray = $this->deptperm_obj->getCurrentViewOnly();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = $this->combineArrays($published_filearray, $userperm_filearray);
		$result_array = $this->combineArrays($result_array, $deptperm_filearray);
		return $result_array;
	}
	
	function getViewableFileOBJs()
	{	
                return $this->convertToFileDataOBJ($this->getViewableFileIds());	
        }
	
	function getReadableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentReadRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentReadRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = $this->combineArrays($published_filearray, $userperm_filearray);
		$result_array = $this->combineArrays($result_array, $deptperm_filearray);
		return $result_array;
	}

	function getReadableFileOBJs()
	{	
                return $this->convertToFileDataOBJ($this->getReadableFileIds());	
        }
	
	function getWriteableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentWriteRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentWriteRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = $this->combineArrays($published_filearray, $userperm_filearray);
		$result_array = $this->combineArrays($result_array, $deptperm_filearray);
		return $result_array;
	}

	function getWriteableFileOBJs()
	{	
                return $this->convertToFileDataOBJ($this->getWriteableFileIds());	
        }
	
	function getAdminableFileIds()
	{
		$userperm_filearray = $this->userperm_obj->getCurrentAdminRight();
		$deptperm_filearray = $this->deptperm_obj->getCurrentAdminRight();
		$published_filearray = $this->user_obj->getPublishedData(1);
		$result_array = $this->combineArrays($published_filearray, $userperm_filearray);
		$result_array = $this->combineArrays($result_array, $deptperm_filearray);
		return $result_array;
	}

	function getAdminableFileOBJs()
	{	
                return $this->convertToFileDataOBJ($this->getAdminableFileIds());	
        }

	function combineArrays($high_priority_array, $low_priority_array)
	{
		$found = false;
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
			if(!isset($found))
			{
				$result_array[$result_array_index++] = $low_priority_array[$l];
			}
			$found = false;
		}
		return $result_array;
	}
	
        function convertToFileDataOBJ($fid_array)
	{
		$filedata_array = array();
		for($i = 0; $i<sizeof($fid_array); $i++)
		{
			$filedata_array[$i] = new FileData($fid_array[$i][0], $this->connection, $this->database, "data");
		}
		return $filedata_array;
	}
  
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
