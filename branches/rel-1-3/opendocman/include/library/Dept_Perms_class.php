<?php 
/**
   Written By: Nguyen Duy Khoa
   Last Modified: 02/07/2003
   Email: knguyen@ksys.serverbox.org

   Dept_Perms is designed to handle permission settings
   of each department.
 */

if( !defined('Dept_Perms_class') )
{
	define('Dept_Perms_class', 'true');

	class Dept_Perms extends databaseData
	{
		/**\protectedsection*/
		var $fid;
		var $id;
		var $rights;
		var $file_obj;
		var $connection, $database;
		/**\publicsection*/
		/**
		  @param INT id
		  @param INT connection database connection
		  @param STRING database database name
		 */
		function Dept_Perms($id, $connection, $database)
		{
			$this->id = $id;  // this can be fid or uid
			$this->connection = $connection;
			$this->database = $database;
		}
		/**
		  Returns a list of id numbers that this department object has VIEW right to
		  @returns INT[] id_array
		 */
		function getCurrentViewOnly()
		{	
			return $this->loadData_UserPerm($this->VIEW_RIGHT);	
		}
		/**
		  Returns a list of id numbers that this department object has NONE right to.
		  NONE right can inherit while a FORBIDDEN cannot.
		  @returns INT[] id_array
		 */
		function getCurrentNoneRight()
		{	
			return $this->loadData_UserPerm($this->NONE_RIGHT);	
		}
		/**
		  Returns a list of id numbers that this department object has READ right to.
		  @returns INT[] id_array
		 */
		function getCurrentReadRight()
		{	
			return $this->loadData_UserPerm($this->READ_RIGHT);	
		}
		/**
		  Returns a list of id numbers that this department object has WRITE (MODIFY) right to.
		  @returns INT[] id_array
		 */
		function getCurrentWriteRight()
		{	
			return $this->loadData_UserPerm($this->WRITE_RIGHT);	
		}
		/**
		  Returns a list of id numbers that this department object has ADMIN right to.
		  @returns INT[] id_array
		 */
		function getCurrentAdminRight()
		{	
			return $this->loadData_UserPerm($this->ADMIN_RIGHT);	
		}
		/** loadData_userPerm($right) returns a list of files that 
		  the department that this OBJ represents has authority >=
		  than $right 
		  @param INT right
		  @returns INT[] id_array
		 */
		function loadData_UserPerm($right)
		{
			$index = 0;
			$fileid_array = array();
			$query = "SELECT $this->TABLE_DEPT_PERMS.fid FROM $this->TABLE_DATA, $this->TABLE_DEPT_PERMS WHERE $this->TABLE_DEPT_PERMS.rights >= $right AND $this->TABLE_DEPT_PERMS.dept_id=$this->id AND $this->TABLE_DATA.id=$this->TABLE_DEPT_PERMS.fid AND $this->TABLE_DATA.publishable=1";
			$result = mysql_query($query, $this->connection) or die("Error in querying: $query" .mysql_error());
			$llen = mysql_num_rows($result);
			while( $index< $llen ) 
			{
				list($fileid_array[++$index] ) = mysql_fetch_row($result);	
			}
			return $fileid_array;		
		}
		/** canView() return a boolean on whether or not this department
		  has view right to the file whose ID is $data_id
		  @param INT data_id
		  @returns BOOL
		 */
		function canView($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			/* check  to see if this department doesn't have a forbidden right or 
			   if this file is publishable*/
			if(!$this->isForbidden($data_id) and $filedata->isPublishable() )
			{
				// return whether or not this deptartment can view the file
				if($this->canDept($data_id, $this->VIEW_RIGHT))
					return true;
				else
					false;
			}
			return false;
		}
		/** canRead() return a boolean on whether or not this department
		  has read right to the file whose ID is $data_id
		  @param INT data_id
		  @returns BOOL
		 */
		function canRead($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			/* check  to see if this department doesn't have a forbidden right or 
			   if this file is publishable*/
			if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
			{
				// return whether or not this deptartment can read the file
				if($this->canDept($data_id, $this->READ_RIGHT) or !$filedata->isPublishable($data_id) )
					return true;
				else
					false;
			}
			return false;
		}
		/** 
		  canWrite($data_id) return a boolean on whether or not this department
		  has modify right to the file whose ID is $data_id
		  @param INT data_id
		  @returns BOOL
		 */
		function canWrite($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			/* check  to see if this department doesn't have a forbidden right or 
			   if this file is publishable*/
			if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
			{
				// return whether or not this deptartment can modify the file
				if($this->canDept($data_id, $this->WRITE_RIGHT))
					return true;
				else
					false;
			}

		}
		/** canAdmin($data_id) return a boolean on whether or not this department
		  has admin right to the file whose ID is $data_id
		  @param INT data_id
		  @returns BOOL
		 */
		function canAdmin($data_id)
		{
			$filedata = new FileData($data_id, $this->connection, $this->database);
			/* check  to see if this department doesn't have a forbidden right or 
			   if this file is publishable*/
			if(!$this->isForbidden($data_id) or !$filedata->isPublishable() )
			{
				// return whether or not this deptartment can admin the file
				if($this->canDept($data_id, $this->ADMIN_RIGHT))
					return true;
				else
					false;
			}

		}
		/** isForbidden($data_id) return a boolean on whether or not this department
		  has forbidden right to the file whose ID is $data_id
		  @param INT data_id
		  @returns BOOL
		 *EX:
		 $dpobj = new Dept_Perm($dept_id, $connection, $database);
		 if( $dpobj.isForbidden($data_id) != $dpobj->error_code && $dpobj.isForbidden($data_id) == false)
		 {
		 ......
		 } 
		 */
		function isForbidden($data_id)
		{
			$this->error_flag = true; // reset flag
			$right = -1;
			$query = "SELECT $this->database.$this->TABLE_DEPT_PERMS.rights FROM $this->database.$this->TABLE_DEPT_PERMS WHERE $this->TABLE_DEPT_PERMS.dept_id = $this->id AND $this->TABLE_DEPT_PERMS.fid = $data_id";
			$result = mysql_query($query, $this->connection) or die("Error in query" .mysql_error() );
			if(mysql_num_rows($result) == 1)
			{
				list ($right) = mysql_fetch_row($result);
				if($right == $this->FORBIDDEN_RIGHT)
					return true;
				else
					return false;
			}
			else 
			{
				$this->error = "Non-unique database entry found in $this->database.$this->TABLE_DEPT_PERMS";
				$this->error_flag = false;
				return 0;
			}	
		}
		/**
		  Returns a BOOL on whether or not this deparment has $right
		  right on file with data id of $data_id
		  @param INT data_id
		  @param INT right
		  @returns BOOL
		 */
		function canDept($data_id, $right)
		{
			$query = "SELECT * FROM $this->TABLE_DEPT_PERMS WHERE $this->TABLE_DEPT_PERMS.dept_id = $this->id and $this->TABLE_DEPT_PERMS.fid = $data_id AND $this->TABLE_DEPT_PERMS.rights >= $right";
			$result = mysql_query($query, $this->connection) or die ("Error in querying: $query" .mysql_error() );

			switch(mysql_num_rows($result) )
			{
				case 1: return true; break;
				case 0: return false; break;
				default : $this->error = 'non-unique uid: $this->id'; break;
			}
		}
		/** Returns the numeric permission setting of this department for the file with
		  ID nuber ob $data_id.  
		  @param INT data_id
		  @returns INT
		 */
		function getPermission($data_id)
		{
			$query = "SELECT $this->TABLE_DEPT_PERMS.rights FROM $this->TABLE_DEPT_PERMS WHERE $this->TABLE_DEPT_PERMS.dept_id = $this->id and $this->TABLE_DEPT_PERMS.fid = $data_id";
			$result = mysql_query($query, $this->connection) or die("Error in query: .$query" . mysql_error() );
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
?
