<?php
if( !defined("databaseData_class") );
{
	define("databaseData_class", "true", false);

   class databaseData   //DO NOT INSTANTIATE THIS ABSTRACT CLASS
   {
      	var $name;
		var $id;
		var $connection;
		var $tablename;
		var $error;
		var $field_name;
		var $field_id;
		var $result_limit;  

	// MUST IMPLEMENT $RESULT_LIMIT SO THAT DATA CLASS CAN USE THIS FEATURE AND DISABLE THE LIMIT.  NEED A WHILE LOOOP
		
	function databaseData($id, $connection, $database)
	{
		$this->connection = $connection;
		$this->database = $database;
		$this->setId($id);
		$this->result_limit = 1;
	}
	function setTableName($tablename)
	{	
		$this->tablename = $tablename;	
	}
	function setId($id)
	{
		$this->id = $id;
		$this->name = $this->findName();
	}
	function getName()
	{
		return $this->name;	
	}

	function getId()
	{
		return $this->id;	
	}
	
	function findId()
	{
		$query = "SELECT {$this->tablename}.$this->field_id FROM {$this->tablename} WHERE {$this->tablename}.$this->field_name='$this->name'";
		$result = mysql_db_query($this->database, $query, $this->connection) or die ("Error in query: $query. " . mysql_error());

		if( mysql_num_rows($result) > $this->result_limit AND result_limit != 'UNLIMITED')
		{
			$this->error='Error: non-unique';
		}
		elseif (mysql_num_rows($result) == 0)
		{
			$this->error = 'Error: unable to fine id in database';
		}
		else
		{
			list($id) = mysql_fetch_row($result);
		}
		return $id;
	}
	function findName()
	{
		$query = "SELECT $this->tablename.$this->field_name FROM $this->tablename WHERE {$this->tablename}.$this->field_id = $this->id";
		$result = mysql_db_query($this->database, $query, $this->connection) or die ("Error in query: " .$query . mysql_error());
		if(mysql_num_rows($result) > $this->result_limit AND result_limit != 'UNLIMITED')
		{
			$this->error='Error: non-unique';
		}
		elseif (mysql_num_rows($result) == 0)
		{	
			$this->error = 'Error: unable to fine id in database';
		}
		else
		{
			list($name) = mysql_fetch_row($result);
		}
		return $name;
	}
	function reloadData() //assuming that userid will never change
	{
		$this->setId($this->id);
	}
	
	function getError()
	{	
		return $this->error;	
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
			if(!$found)
			{
				$result_array[$result_array_index++] = $low_priority_array[$l];
			}
			$found = false;
		}
		return $result_array;
	}

   }
// end inclusion control
}
?>
