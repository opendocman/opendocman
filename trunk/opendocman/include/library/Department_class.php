<?php	

	/*	
	Written By: Nguyen Duy Khoa
	Last Modified: 02/07/2003
	Email: knguyen@ksys.serverbox.org

	Department class is an extended class of the abstractive databaseData
	class.  The only difference is that it provides it's own constructor
	to handle its own characteristics.  
	*/

if( !defined('Department_class') )
{
	define('Department_class', 'true', false);
	class Department extends databaseData
	{
		/**
		  \publicsection
		 @param INT     id
		 @param INT     connection
		 @param STRING  database
		 */
	function Department($id, $connection, $database)
	{
		$this->field_name = 'name';
		$this->field_id = 'id';
		$this->result_limit = 1; //there is only 1 department with a certain department_id and department_name
        $this->TABLE_ADMIN = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_ADMIN;
        $this->TABLE_CATEGORY = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_CATEGORY;
        $this->TABLE_DATA = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_DATA;
        $this->TABLE_DEPARTMENT = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_DEPARTMENT;
        $this->TABLE_DEPT_PERMS = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_DEPT_PERMS;
        $this->TABLE_DEPT_REVIEWER = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_DEPT_REVIEWER;
        $this->TABLE_LOG = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_LOG;
        $this->TABLE_RIGHTS = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_RIGHTS;
        $this->TABLE_USER = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_USER;
        $this->TABLE_USER_PERMS = $GLOBALS['CONFIG']['table_prefix'] . $this->TABLE_USER_PERMS;
		$this->tablename = $this->TABLE_DEPARTMENT;
		databaseData::databaseData($id, $connection, $database);
	}
  } 
}
?>
