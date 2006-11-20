<?php	
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
		$this->tablename = 'department';
		databaseData::databaseData($id, $connection, $database);
	}
  } 
}
?>
