<?php
if( !defined("databaseData_class") );
{
	define("databaseData_class", "true", false);

   class databaseData   //DO NOT INSTANTIATE THIS ABSTRACT CLASS
   {
      	var $TABLE_ADMIN = 'admin';
      	var $TABLE_CATEGORY = 'category';
      	var $TABLE_DATA = 'data';
      	var $TABLE_DEPARTMENT = 'department';
      	var $TABLE_DEPT_PERMS = 'dept_perms';
      	var $TABLE_DEPT_REVIEWER = 'dept_reviewer';
      	var $TABLE_LOG = 'log';
      	var $TABLE_RIGHTS = 'rights';
      	var $TABLE_USER = 'user';
      	var $TABLE_USER_PERMS = 'user_perms';
      	var $FORBIDDEN_RIGHT = -1;
		var $NONE_RIGHT = 0;
		var $VIEW_RIGHT = 1;
		var $READ_RIGHT = 2;
		var $WRITE_RIGHT = 3;
		var $ADMIN_RIGHT = 4;
   		var $name;
		var $id;
		var $connection;
		var $database;
		var $tablename;
		var $error;
		var $field_name;
		var $field_id;
		var $result_limit;  

	function databaseData($id, $connection, $database)
	{
		$this->connection = $connection;
		$this->database = $database;
		$this->setId($id); //setId not only set the $id data member but also find and set name
		$this->result_limit = 1; //expect unique data fields on default
	}
	function setTableName($tablename)
	{	
		$this->tablename = $tablename;	
	}
	function setId($id)
	{
		/*setId($id) sets the data member $id and it also look
		a name that is correspondent to that id and set it to
		the data member field $name*/
		$this->id = $id;
		$this->name = $this->findName();
	}
	function setName($name)
	{
		/*setName can only be used under the assumption that
		the name field in the DB is unquie, e.g. username*/
		$this->name = $name;
		$this->id = findId();
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
		$query = "SELECT {$this->database}.{$this->tablename}.$this->field_id FROM {$this->database}.{$this->tablename} WHERE {$this->database}.{$this->tablename}.$this->field_name='$this->name'";
		$result = mysql_query($query, $this->connection) or die ("Error in query: $query. " . mysql_error());

		if( mysql_num_rows($result) > $this->result_limit AND result_limit != 'UNLIMITED')
		{
			/*if the result is more than expected error var is set*/
			$this->error='Error: non-unique';
		}
		elseif (mysql_num_rows($result) == 0) // record must exist.  Error message is stored
		{
			$this->error = 'Error: unable to fine id in database';
		}
		else
		{
			list($id) = mysql_fetch_row($result);
		}
		return $id;
	}
	/* logic in findName() is simular to findId().  Please look at findId()'s 
	comments if you need help with this function */
	function findName()
	{
		$name = '';
		$query = "SELECT {$this->database}.$this->tablename.$this->field_name FROM {$this->database}.$this->tablename WHERE {$this->database}.{$this->tablename}.$this->field_id = $this->id";
		$result = mysql_query($query, $this->connection) or die ("Error in query: " .$query . mysql_error());
		
		if(mysql_num_rows($result) > $this->result_limit AND result_limit != 'UNLIMITED')
		{
			$this->error='Error: non-unique';
		}
		elseif (mysql_num_rows($result) == 0)
		{	
			$this->error = 'Error: unable to find id in database';
		}
		else
		{
			list($name) = mysql_fetch_row($result);
                        return $name;
		}
	}
	function reloadData() //assuming that userid will never change
	{
		/* Since all the data are set at the time when $id or $name
		is set.  If another program access the DB and changes any
		information, this OBJ will no longer contain up-to-date
		information.  reloadData() will reload all the data */
		
		$this->setId($this->id);
	}
	
	function getError()
	{	
		/* Get error will return the last thrown error */
		return $this->error;	
	}

	function combineArrays($high_priority_array, $low_priority_array)
	{
		/* combineArrays() uses a linear search agolrithm with the
		cost of n*n, n being the size of the biggest array.  combineArrays()
		gives $high_priority_array the advantage by merging the
		low_priority_aray onto it.  One can look at these two arrays
		as 2 sets and cobineArrays acts as a union operator.
		For briefness, let's $high = $high_priority_array and
		$low = $low_priority_array */
		
		$found = false;
		$result_array = array();
		$result_array = $high_priority_array; //$high is being kept
		$result_array_index = sizeof($high_priority_array);
		for($l = 0 ; $l<sizeof($low_priority_array); $l++) //iterate through $low
		{
			/* each $low element will be compared with
			every $high element*/
			for($r = 0; $r<sizeof($result_array); $r++)
			{
				if($result_array[$r] == $low_priority_array[$l])
				{
					/* if a $low element is already in the 
					$high array, it is ignored */
					$r = sizeof($result_array);
					$found = true;
				}
			}
			/* if certain $low element is not found in $high, it 
			will be append to the back of high*/
			if(!$found)
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
			$filedata_array[$i] = new FileData($fid_array[$i], $this->connection, $this->database, "data");
		}
		return $filedata_array;
	}

   }
// end inclusion control
}
?>
