<?php
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
	var $view_users;
	var $read_users;
	var $write_users;
	var $admin_users;
	
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
	    $query = "SELECT * FROM $this->tablename WHERE $this->tablename.id = $this->id";
	    $result = mysql_query($query, $this->connection);
	    switch(mysql_num_rows($result))
	    {
	      case 1: return true; break;
	      case 0: return false; break;
	      default: $this->error = 'Non-unique'; return $this->error; break;
	    }
	}
	/* loadData() is a more complex version of base class's loadData. 
	This function load up all the fields in data table.*/
	function loadData()
	{
		$query = "SELECT $this->tablename.category,$this->tablename.owner, 
			$this->tablename.created, $this->tablename.description, 
			$this->tablename.comment, $this->tablename.status, 
			$this->tablename.department FROM $this->tablename 
			WHERE $this->tablename.id = $this->id";
		
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
		if( mysql_num_rows($result) == $this->result_limit )
		{
			while( list($category, $owner, $created_date, $description, $comment, $status, $department) = mysql_fetch_row($result) )
			{
				$this->category = $category;
				$this->owner = $owner;
				$this->created_date = $created_date;
				$this->description = $description;
				$this->comment = $comment;
				$this->status = $status;
				$this->department = $department;
			}
		}
		else
			$this->error = 'Non unique file id';
	}
	// return this file's category id
	function getCategory()
	{	return $this->category;		}
	// return this file's category name
	function getCategoryName()
	{	
		$query = "SELECT $this->TABLE_CATEGORY.name FROM $this->TABLE_CATEGORY WHERE $this->TABLE_CATEGORY.id = $this->category";
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
	{	return ($this->getOwner()==$uid);	}
	// return the ID of the owner of this file
	function getOwner()
	{	return $this->owner;		}
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
	{	return $this->description;	}
	// return file commnents
	function getComment()
	{	return $this->comment;		}
	// return an aray of the user id of all the people who has $right right to this file
	function getUserIds($right)
	{
	  $result_array = array();
	  $owner_query = "SELECT owner FROM $this->tablename WHERE id = $this->id";
	  $u_query = "SELECT uid FROM $this->TABLE_USER_PERMS WHERE fid = $this->id and rights >= $right";
	  //query for user who has right less than $right
	  $non_prev_user_query = "SELECT uid FROM $this->TABLE_USER_PERMS WHERE fid = $this->id AND rights < $right"; 
	  
	  $owner_result = mysql_query($owner_query, $this->connection) or die("Error in query: ".$owner_query . mysql_error() );
	  $u_result = mysql_query($u_query, $this->connection) or die("Error in query: " .$u_query . mysql_error() );
	  // result of $non_prev_user_query query.  Look above for more information.
	  $non_prev_u_reslt = mysql_query($non_prev_user_query, $this->connection) or die("Error in query: " .$non_prev_user_query . mysql_error() );  
	  
	  $not_u_uid = array();// array of user_id that are forbidden on the file
	  $d_uid = array();// init for array of dept_id;
	  for($i = 0; $i<mysql_num_rows($non_prev_u_reslt); $i++)
	  	list($not_u_uid[$i]) = mysql_fetch_row($non_prev_u_reslt);
	  
	  $d_query = "SELECT $this->TABLE_USER.id, $this->TABLE_DEPT_PERMS.dept_id 
	  	FROM $this->TABLE_DEPT_PERMS, $this->TABLE_USER WHERE fid = $this->id AND 
	  	$this->TABLE_USER.department = $this->TABLE_DEPT_PERMS.dept_id and 
	  	$this->TABLE_DEPT_PERMS.rights >= $right";
	  
	  for($i=0; $i<sizeof($not_u_uid); $i++)
	  {
		$d_query .= " and $this->TABLE_USER.id != " . $not_u_uid[$i];
	  }
	  $d_result = mysql_query($d_query, $this->connection) or die("Error in query: " .$d_query . mysql_error() );	
	  if(sizeof($owner_result) != 1)
	  {
	    echo 'Error in DB, multiple ownership';
	    exit;
	  }
	  $owner_uid = mysql_fetch_row($owner_result);
	  for($i = 0; $i<mysql_num_rows($u_result); $i++)
	    list($u_uid[$i]) = mysql_fetch_row($u_result);
	  for($i = 0; $i<mysql_num_rows($d_result); $i++)
	    list($d_uid[$i]) = mysql_fetch_row($d_result);
	  
	  if( isset($owner_uid) && isset($u_uid) )
	  {	  $result_array = databaseData::combineArrays($owner_uid, $u_uid);	}
	  if( isset($result_array) && isset($d_uid) )
	  {	  $result_array = databaseData::combineArrays($result_array, $d_uid);	}
	  
	  mysql_free_result($owner_result);
	  mysql_free_result($u_result);
	  mysql_free_result($d_result);
	  return $result_array;
	}
	// return the status of the file
	function getStatus()
	{	return $this->status;		}
	// return a User OBJ of the person who checked out this file
	function getCheckerOBJ()
	{
		$user = new User($this->status, $this->connection, $this->database);
		return $user;
	}
	// return the deparment ID of the file
	function getDepartment()
	{	return $this->department;	}
	// return the name of the deparment of the file
	function getDeptName()
	{
		$query ="SELECT $this->TABLE_DEPARTMENT.name FROM $this->TABLE_DEPARTMENT WHERE $this->TABLE_DEPARTMENT.id = ".$this->getDepartment().';';
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
		if(mysql_num_rows($result) != 1)
		{
			echo('ERROR: Multiple database entries exist in department table.');
			exit;
		}
		list($dept) = mysql_fetch_row($result);
		return $dept;
	}
	// return the date that the file was created on
	function getCreatedDate()
	{	return $this->created_date;	}
	// return the latest modifying date on the file 
	function getModifiedDate()
	{
		$query = "SELECT $this->TABLE_LOG.modified_on FROM $this->TABLE_LOG WHERE $this->TABLE_LOG.id = '$this->id' ORDER BY $this->TABLE_LOG.modified_on DESC LIMIT 1;";
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
	// return the realname of the file
	function getRealName()
	{	return databaseData::getName();	}
	/* getViewRightUserIds(), getReadRightUserIds(), getWriteRightUserIds(), 
	getAdminRightUserIds(), getNoneRightUserIds(), provide interfaces to 
	getUserIds($right).*/
	function getViewRightUserIds()
	{	return $this->getUserIds($this->VIEW_RIGHT);  }	  
	
	function getReadRightUserIds()
	{	return $this->getUserIds($this->READ_RIGHT);  }
	
	function getWriteRightUserIds()
	{	return $this->getUserIds($this->WRITE_RIGHT);  }
	
	function getAdminRightUserIds()
	{	return $this->getUserIds($this->ADMIN_RIGHT);  }
	
	function getNoneRightUserIds()
	{	return $this->getUserIds($this->NONE_RIGHT);  }
	// return an array of user id who are forbidden to this file
	function getForbiddenRightUserIds()
	{
	
	  $u_query = "SELECT $this->TABLE_USER_PERMS.uid FROM $this->TABLE_USER_PERMS WHERE $this->TABLE_USER_PERMS.fid = $this->id and $this->TABLE_USER_PERMS.rights = $this->FORBIDDEN_RIGHT";
	  $d_query = "SELECT $this->TABLE_USER.id, $this->TABLE_DEPT_PERMS.dept_id 
	  	FROM $this->TABLE_DEPT_PERMS, $this->TABLE_USER WHERE 
	  	$this->TABLE_DEPT_PERMS.fid = $this->id and 
	  	$this->TABLE_USER.department = $this->TABLE_DEPT_PERMS.dept_id 
	  	AND $this->TABLE_DEPT_PERMS.rights = $this->FORBIDDEN_RIGHT";
	  
	  $u_result = mysql_query($u_query, $this->connection) or die("Error in query: " .$u_query . mysql_error() );
	  $d_result = mysql_query($d_query, $this->connection) or die("Error in query: " .$d_query . mysql_error() );
	  $d_uid = array();
	  $u_uid = array();
	  for($i = 0; $i<mysql_num_rows($u_result); $i++)
	    list($u_uid[$i]) = mysql_fetch_row($u_result);
	  for($i = 0; $i<mysql_num_rows($d_result); $i++)
	    list($d_uid[$i]) = mysql_fetch_row($d_result);

	  $result_array = databaseData::combineArrays(array(), $u_uid);
	  $result_array = databaseData::combineArrays($result_array, $d_uid);
	  
	  mysql_free_result($u_result);
	  mysql_free_result($d_result);
	  return $result_array;

	
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
		$query = "SELECT publishable FROM $this->TABLE_DATA WHERE id = '$this->id'";
		$result = mysql_query($query, $this->connection) or die('Error in query'. mysql_error());
		if(mysql_num_rows($result) != 1)
		{
			echo('DB error.  Unable to locate file id ' . $this->id . ' in table data.  Please contact ' . $GLOBALS['CONFIG']['site_mail'] . 'for help');
			exit;
		}
		list($publishable) = mysql_fetch_row($result);
		mysql_free_result($result);
		return $publishable;
	}
	// this function sets the publisable field in the data table to $boolean
	function Publishable($boolean = true)
	{
		$query = "UPDATE $this->TABLE_DATA SET publishable ='$boolean', $this->TABLE_DATA.reviewer = '$this->id' WHERE id = '$this->id'";
		$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
	}
	// return the user id of the reviewer
	function getReviewerID()
	{
		$query = "SELECT $this->TABLE_DATA.reviewer FROM $this->TABLE_DATA WHERE $this->TABLE_DATA.id = '$this->id'";
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
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.reviewer_comments='$comments' WHERE $this->TABLE_DATA.id='$this->id'";
		$result = mysql_query($query, $this->connection) or
		die("Error in query: $query" . mysql_error());
	}
	// return the reviewer's comment toward this file
	function getReviewerComments()
	{
		$query = "SELECT $this->TABLE_DATA.reviewer_comments FROM $this->TABLE_DATA WHERE $this->TABLE_DATA.id='$this->id'";
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
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.publishable = 2 WHERE $this->TABLE_DATA.id = $this->id";
		$result = mysql_query($query, $this->connection) or
			die("Error in query: $query" . mysql_error());
	}
	function undelete()
	{
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.publishable = 0 WHERE $this->TABLE_DATA.id = $this->id";
		$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
	}
  }
}
?>
