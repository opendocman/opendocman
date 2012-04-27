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
	/**\privatesection*/
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
	var $filesize;
	var	$isLocked;
	var $anonymous;
	/**\publicsection*/	
	function FileData($id, $connection, $database)
	{
		$this->field_name = 'realname';
		$this->field_id = 'id';
		$this->result_limit = 1;  //EVERY FILE IS LISTED UNIQUELY ON THE DATABASE DATA;
		$this->tablename = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_DATA;
		databaseData::databaseData($id, $connection, $database);
		
		$this->loadData();
	}
	/** 
		Returns a boolean whether this file exists
		@returns BOOL
		*/
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
	/** loadData() is a more complex version of base class's loadData. 
	This function load up all the fields in data table.
		@returns VOID
	*/
	function loadData()
	{
		$query = "SELECT $this->tablename.category,$this->tablename.owner, 
			$this->tablename.created, $this->tablename.description, 
			$this->tablename.comment, $this->tablename.status, 
			$this->tablename.department, $this->tablename.anonymous 
			FROM $this->tablename WHERE $this->tablename.id = $this->id";
		
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
		if( mysql_num_rows($result) == $this->result_limit )
		{
			while( list($category, $owner, $created_date, $description, $comment, $status, $department, $anonymous) = mysql_fetch_row($result) )
			{
				$this->category = $category;
				$this->owner = $owner;
				$this->created_date = $created_date;
				$this->description = $description;
				$this->comment = $comment;
				$this->status = $status;
				$this->department = $department;
				$this->anonymous = $anonymous;
			}
		}
		else
			$this->error = 'Non unique file id';
		$this->isLocked = $this->status==-1;
	}
	/** 
		Return database version of the filesize.  Originally ODM has the filesize recorded
	into the database.  Since the filesize only changes when the user upload a new file, so it
	made sense to have the filesize be somewhat constant.  As ODM DB grows larger, it becomes
	more practical to move the filesize look up duty to the OS of the server.  Even though ODM no
	longer uses this function, we still want to keep it around in case we ever need to swing 
	back to the original idea.  For those with relatively small ODM DB and a busy HTTP server, 
	DB filesize will be better for you.
	@returns INT
	*/
	function getFileSize()
	{	return $this->filesize;	}
	
	/** Returns this file's category id.  This function is much more useful to programmer than getCategoryName()
	@returns ID
	*/
	function getCategory()
	{	return $this->category;		}
	
	/** Returns this file's category name. This function is more useful to ODM users and administrators than getFileSize()
	@returns STRING
	*/
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
	/** Returns a BOOL on whether the user ID $uid is the owner of this file
	@param INT uid
	@returns BOOL
	*/
	function isOwner($uid)
	{	return ($this->getOwner()==$uid);	}
	
	/** Returns whether this file can by anonymously viewed
	@returns VOID
	*/
	function isAnonymous()
	{	return $this->anonymous;	}
	
	/** Returns the ID of the owner of this file
	@returns INT id
	*/
	function getOwner()
	{	return $this->owner;		}
	/** Returns the username of the owner
	@returns STRING
	*/
	function getOwnerName()
	{	
		$user_obj = new User($this->owner, $this->connection, $this->database);
		return $user_obj->getName();
	}
	/** return owner's full name in an array where index=0 corresponds to the last name
		and index=1 corresponds to the first name
		@returns STRING[]
	*/
	function getOwnerFullName()
	{	
		$user_obj = new User($this->owner, $this->connection, $this->database);
		return $user_obj->getFullName();
	}
	/** return the owner's dept ID.  Often, this is also the department of the file.
		if the owner changes his/her department after he/she changes department, then
		the file's department will not be the same as it's owner's.
		@returns INT
	*/
	function getOwnerDeptId()
	{
		$user_obj = new User($this->getOwner(), $this->connection, $this->database);
		return $user_obj->getDeptId();
	}
	/** This function serve the same purpose as getOwnerDeptId() except that it returns
		the department name instead of department id
		@returns STRING
	*/
	function getOwnerDeptName()
	{
		$user_obj = new User($this->getOwner(), $this->connection, $this->database);
		return $user_obj->getDeptName();
	}
	/** Returns file description
	@returns STRING
	*/
	function getDescription()
	{	return $this->description;	}
	/** Returns file commnents
	@returns STRING
	*/
	function getComment()
	{	return $this->comment;		}
	/** return an aray of the user id of all the people who has $right right to this file
	  @returns STRING
	 */
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
	/** Return the status of the file
	    @returns STRING
	*/
	function getStatus()
	{	return $this->status;		}
	/** Set the status of the file
		0 --> Free, unlocked, checkout-able
		+Z (Positive integer number) --> This file is check out to the person whom this ID belongs to
		-Z (Negative integer number) --> This file is currently locked and cannot be checked out.
		@param +/- INT
		@returns VOID
	*/	
	function setStatus($value)
	{	mysql_query('UPDATE data set status=' . $value . ' where data.id = ' . $this->id) or die(mysql_error());}
	/** 
		Returns a User OBJ of the person who checked out this file
		@returns USER_OBJ	
	*/
	function getCheckerOBJ()
	{
		$user = new User($this->status, $this->connection, $this->database);
		return $user;
	}
	/** 
		Returns the deparment ID of the file
		@returns INT
	*/
	function getDepartment()
	{	return $this->department;	}
	/** 
		Returns the name of the deparment of the file
		@returns STRING
	*/
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
	/**	Returns the date that the file was created on
		@returns STRING
	*/
	function getCreatedDate()
	{	return $this->created_date;	}
	/** Returns the latest modifying date on the file
		@returns STRING
	*/
	function getModifiedDate()
	{
		/*$query = "SELECT $this->TABLE_LOG.modified_on FROM $this->TABLE_LOG WHERE $this->TABLE_LOG.id = '$this->id' ORDER BY $this->TABLE_LOG.modified_on DESC LIMIT 1;";
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());
                if( mysql_num_rows($result) == $this->result_limit)
                        list($name) = mysql_fetch_row($result);
                else
                {
                                $this->error = 'Non unique file id';
                                return $this->error;
                }*/
        
        $query = "SELECT modified_on FROM $this->TABLE_LOG WHERE id = '$this->id' ORDER BY modified_on DESC limit 1;";
		$result = mysql_query($query) or die ("Error in query: $query. " . mysql_error());
        list($name) = mysql_fetch_row($result);       
        return $name;
	}
	/** Returns the realname of the file
		@returns STRING
	*/
	function getRealName()
	{	return databaseData::getName();	}
	/** Returns ID of users who have "VIEW_RIGHT" or more
		@returns INT[]
	*/
	function getViewRightUserIds()
	{	return $this->getUserIds($this->VIEW_RIGHT);  }	  
	/** Returns ID of users who have "READ_RIGHT" or more
	        @returns INT[]
			    */
	function getReadRightUserIds()
	{	return $this->getUserIds($this->READ_RIGHT);  }
	/** Returns ID of users who have "WRIT_RIGHT" or more
	        @returns INT[]
			    */
	function getWriteRightUserIds()
	{	return $this->getUserIds($this->WRITE_RIGHT);  }
	/** Returns ID of users who have "ADMIN_RIGHT" or more
	        @returns INT[]
			    */
	function getAdminRightUserIds()
	{	return $this->getUserIds($this->ADMIN_RIGHT);  }
	/** Returns ID of users who have "NONE_RIGHT" or more
	        @returns INT[]
			    */
	function getNoneRightUserIds()
	{	return $this->getUserIds($this->NONE_RIGHT);  }
	/** Returns ID of users who have "NONE_RIGHT" or more
	        @returns INT[]
			    */
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
	/** Converts an array of user ID's into an array of user object
		@param INT[]
		@returns USER_OBJ[]
	*/
	function toUserOBJs($uid_array)
	{
	  $UserOBJ_array = array();
	  for($i = 0; $i<sizeof($uid_array); $i++)
	  { 
	    $UserOBJ_array[$i] = new User($uid_array[$i], $this->connection, $this->database);
	  }
	  return $UserOBJ_array;
	}
	/** Returns a boolean on whether or not this file is publisable
		@returns BOOL
	*/
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
	/**
		Returns whether or not a file is archived
		@returns BOOL
	*/
	function isArchived()
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
		return ($publishable == 2);
	}
	/** This function sets the publisable field in the data table to $boolean
		@param BOOL boolean
		@returns VOID
	*/
	function Publishable($boolean = true)
	{
		$query = "UPDATE $this->TABLE_DATA SET publishable ='$boolean', $this->TABLE_DATA.reviewer = '$this->id' WHERE id = '$this->id'";
		$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
	}
	/** Returns the user id of the reviewer
		@returns INT
	*/
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
	/** Returns the username of the reviewer
		@returns STRING
	*/
	function getReviewerName()
	{
		$reviewer_id = $this->getReviewerID();
		if(isset($reviewer_id))
		{	
			$user_obj = new User($reviewer_id, $this->connection, $this->database);
			return $user_obj->getName();
		}
  	}
  	/** Returns a user object for the reviewer
		@returns USER_OBJ
	*/
  	function getReviewerOBJ()
  	{
  		return (new User($this->getReviewerID(), $this->connection, $this->database));
  	}
  	/** Sets $comments into the reviewer comment field in the DB
		@param STRING comments
		@returns VOID
	*/
	function setReviewerComments($comments)
	{
        $comments=addslashes($comments);
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.reviewer_comments='$comments' WHERE $this->TABLE_DATA.id='$this->id'";
		$result = mysql_query($query, $this->connection) or
		die("Error in query: $query" . mysql_error());
	}
	/** Returns the reviewer's comment toward this file
		@returns STRING
	*/
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
	/** Archive this file
		@returns VOID
	*/
	function temp_delete()
	{
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.publishable = 2 WHERE $this->TABLE_DATA.id = $this->id";
		$result = mysql_query($query, $this->connection) or
			die("Error in query: $query" . mysql_error());
	}
	/** Unarchive this file
		@returns VOID
	*/
	function undelete()
	{
		$query = "UPDATE $this->TABLE_DATA SET $this->TABLE_DATA.publishable = 0 WHERE $this->TABLE_DATA.id = $this->id";
		$result = mysql_query($query, $this->connection) or die("Error in query: $query" . mysql_error());
	}
	/** Returns whether if this file is locked
		@returns BOOL
	*/
	function isLocked()
	{	return $this->isLocked;	}
  }
}
?>
