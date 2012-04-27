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
		function Department($id, $connection, $database)
		{
			$this->field_name = 'name';
			$this->field_id = 'id';
			$this->result_limit = 1; //there is only 1 department with a certain department_id and department_name
			$this->tablename = $this->TABLE_DEPARTMENT;
			databaseData::databaseData($id, $connection, $database);
		}
	} 
}
?>
