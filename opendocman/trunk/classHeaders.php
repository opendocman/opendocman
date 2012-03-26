<?php
/*
classHeaders.php - loads common classes
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.
 * 
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

if( !defined('classHeader') )
{
    define('classHeader', 'true', false);
    include_once('databaseData_class.php');
    include_once('User_class.php');
    include_once('Department_class.php');
    include_once('User_Perms_class.php');
    include_once('FileData_class.php');
    include_once('Department_class.php');
    include_once('Dept_Perms_class.php');
    include_once('UserPermission_class.php');

    /*
     * @param $hi_priority_array array 
     * @param $hi_postfix 
     * @param $low_priority_array
     * @param $low_postfix
     */
    function advanceCombineArrays($hi_priority_array, $hi_postfix, $low_priority_array, $low_postfix)
    {
        //merge higher priority onto lower priority one.
        $user_rights = array();
        $k = 0;
        $foundFlag = false;   
        //create a multidimension array: element of view and right of view
        for($i = 0; $i<sizeof($low_priority_array); $i++)
        {
            $user_rights[$i] = array($low_priority_array[$i], $low_postfix);         
        }

        $k = sizeof($user_rights);
        for($m = 0; $m<sizeof($hi_priority_array); $m++)
        {
            for($u = 0; $u<sizeof($user_rights); $u++)
            {
                if($user_rights[$u][0] == $hi_priority_array[$m] and $hi_postfix!='NULL' )
                {
                    $user_rights[$u][1] = $hi_postfix;
                    $foundFlag = true;
                }
                if($user_rights[$u][0] == $hi_priority_array[$m][0] and $hi_postfix =='NULL')
                {
                    $user_rights[$u][1] = $hi_priority_array[$m][1];
                    $foundFlag = true;
                }

            }
            if($foundFlag==false & $hi_postfix != 'NULL')
            {
                $user_rights[$k++]= array($hi_priority_array[$m], $hi_postfix);
            }
            if($foundFlag==false & $hi_postfix == 'NULL')
            {
                $user_rights[$k++]= $hi_priority_array[$m];
            }
            $foundFlag = false;
        }
        return $user_rights;
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
                if($result_array[$r] == $low_priority_array[$l] && $high_priority_array[$r] == true)
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
    /*
	      2DArray --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	
	//////////////////////////////////////////////////HEADER FOR databaseData CLASS///////////////////////////////////////////////////////////
	class databaseData   //DO NOT INSTANTIATE THIS ABSTRACT CLASS.  BACKBONE OF THE DATABASE ENCAPSULATION_LAYER CLASSES databaseData_class.php
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
	   
	   NULL	    databaseData($connection, $database, $tablename)  //NON INSTANTIATABLE
	   void	    setName($name)    -->   if setName($name) is run, this class will load data that correpond to $name, assuming $name is unique     
	   void	    setId($id)	      -->   if setId($id) is run, FileDataOBJ class will load data that correpond to $id, assuming $id is unique
	   string   getName()	      -->   return the OBJ's name 
	   int	    getId()	      -->   return the OBJ's id
	   int	    findId()	      -->   find and return the OBJ's id using its name.  (assuming name is unique)
	   string   findName()	      -->   find and return the OBJ's name using its id.  (assuming id is unique)
	   void	    reloadData()      -->   reload OBJ's infor using id (assuming that id will never change)
	   string   getError()	      -->   all of the above function will register an error when it occurs.  getError() will return the last occured error
	}
	//////////////////////////////////////////////////////////HEADER FOR databaseData CLASS//////////////////////////////////////////////////////
	class Department extends databaseData --> databaseData_class.php
	{
		function Department($connection, $database, $tablename) -->sets the extra data required for Department from its base class
	}
	/////////////////////////////////////////////////////HEADER FOR User CLASS//////////////////////////////////////////////////////////////
	class User extends databaseData --> User_class.php
	{
		UserOBJ	  User($connection, $database, $tablename) 
		string	  getDeptName()	    --> return name of the Department of the OBJ
		int	  getDeptId()	    --> return id of the Department
		2DArray	  getPublishedData()   --> return a 2D_array containing.  Refer to User_Perms header for 2D_array format
	}
	////////////////////////////////////////////////////////HEADER FOR FileData CLASS///////////////////////////////////////////////////
	class FileData extends databaseData -->FileData_class.php
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
		var $NONE_RIGHT = 0;
		var $VIEW_RIGHT = 1;
		var $READ_RIGHT = 2;
		var $WRITE_RIGHT = 3;
		var $ADMIN_RIGHT = 4;
		
	      FileDataOBJ FileData($connection, $database, $tablename)
	
	      void     setId($id)	 --> if setId($id) is run, FileDataOBJ class will load data that correpond to $id, assuming $id is unique
	      void     setName($name)	 --> if setName($name) is run, FileDataOBJ class will load data that correpond to $name, assuming $name is unique
	      void     loadData()	 --> load info according id
	      string   getCategory()	 --> return the Category of the OBJ
	      bool     isOwner($uid)	 --> resturn weather or not the $uid is the owner of this file
	      int      getOwner()	 --> return the id of the owner of the file for this OBJ
	      string   getOwnerName()	 --> return the name of the owner
	      string   getDescription()	 --> return the Descripton string of the file
	      string   getComment()	 --> return the Comment string of the file
	      string   getStatus()	 --> return the Status string of of the file
	      int      getDepartment()	 --> return the Department id of the file
	      string   getCreatedDate()	 --> return the CreatedDate string of the file
	}
	
	////////////////////////////////////HEADER FOR ALL MEMBER FUNCTION OF User_Perms_class.php////////////////////////////////////////////////
	class User_Perms 
	{
	   var $fid;
	   var $id;
	   var $rights;
	   var $user_obj;
	   var $file_obj;
	   var $error;
	   var $chosen_mode;
	   var $connection, $database;
	   var $NONE_RIGHT = 0;
	   var $VIEW_RIGHT = 1;
	   var $READ_RIGHT = 2;
	   var $WRITE_RIGHT = 3;
	   var $ADMIN_RIGHT = 4;
	   var $FORBIDEN_RIGHT = 5;
	   var $USER_MODE = 0;
	   var $FILE_MODE = 1;
	   var $USER_PERM_TABLE = "user_perms";
	   var $DEPT_PERM_TABLE = "dept_perms";
	   var $DATA_TABLE = "data";
	   var $USER_TABLE = "user";
	   var $DEPARTMENT_TABLE = "department";
	
	   User_Perms_OBJ 	function User_Perms($id, $connection, $database)
	   2D_array    function getCurrentViewOnly() 	--> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	   2D_array    getCurrentNoneRight()		--> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	   2D_array    getCurrentReadRight() 		--> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	   2D_array    getCurrentWriteRight() 		--> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	   2D_array    getCurrentAdminRight() 		--> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	
	   int	       function getId() 			--> userid 
	   2D_array    function loadData_UserPerm($right)	-->load array[x][y] for certain $right
	   bool	       function canView($data_id)		--> return wether or not this user can view the file with data.id = $data_id
	   bool	       canRead($data_id)			--> return wether or not this user can read the file with data.id = $data_id
	   bool	       canWrite($data_id)			--> return wether or not this user can write the file with data.id = $data_id
	   bool	       canAdmin($data_id)			--> return wether or not this user can admin the file with data.id = $data_id
	   bool	       isForbidden($data_id )			--> return wether or not this user has access on the file with data.id = $data_id
	   bool	       canUser($data_id, $right)		--> return wether or not this user has $right on the file with data.id = $data_id
	}
	//////////////////////////////////////////////////////////HEADER FOR Dept_Perms CLASS////////////////////////////////////////////////////////
	class Dept_Perms  -> Dept_Perms_class.php
	{
		var $fid;
		var $id;
		var $rights;
		var $user_obj;
		var $file_obj;
		var $error;
		var $chosen_mode;
		var $connection, $database;
		
		var $NONE_RIGHT = 0;
		var $VIEW_RIGHT = 1;
		var $READ_RIGHT = 2;
		var $WRITE_RIGHT = 3;
		var $ADMIN_RIGHT = 4;
		var $FORBIDEN_RIGHT = 5;
		var $USER_MODE = 0;
		var $FILE_MODE = 1;
		var $USER_PERM_TABLE = "user_perms";
		var $DEPT_PERM_TABLE = "dept_perms";
		var $DATA_TABLE = "data";
		var $USER_TABLE = "user";
		var $DEPARTMENT_TABLE = "department";
	
	      Dept_PermsOBJ  Dept_Perms($id, $connection, $database)
	      
	      2D_array	  getCurrentViewOnly()	  --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	      2D_array	  getCurrentNoneRight()	  --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	      2D_array	  getCurrentReadRight()	  --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	      2D_array	  getCurrentWriteRight()  --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	      2D_array	  getCurrentAdminRight()  --> array[x][y] where x is the file index, and y=0..2 for fid, owner_id, owner_name
	      int      	  getId()		  --> return the department id.
	      2D_array	  loadData_UserPerm($right)  -->load array[x][y] for certain $right
	      bool	  canView($data_id)	     --> return wether or not this dept can view the file with data.id = $data_id	     
	      bool	  canRead($data_id)	     --> return wether or not this dept can read the file with data.id = $data_id  
	      bool	  canWrite($data_id)	     --> return wether or not this dept can write the file with data.id = $data_id
	      bool	  canAdmin($data_id)	     --> return wether or not this dept can admin the file with data.id = $data_id
	      bool	  isForbidden($data_id)	     --> return wether or not this dept has access to the file with data.id = $data_id
	      bool	  canDept($data_id, $right)  --> return wether or not this dept has $right on the file with data.id = $data_id
	}
	
	////////////////////////////////////////////////HEADER FOR UserPermission CLASS//////////////////////////////////////////////////////////////
	class UserPermission --> UserPermission_class.php
	{
	   var $connection;
	   var $uid;
	   var $database;
	   var $user_obj;
	   var $userperm_obj;
	   var $deptperm_obj;
	
	   UserPermission_OBJ UserPermission($uid, $connection, $database)
	   2D_array	getAllowedFileIds() -->refer to User_Perms header for array format.  Returns all files that this user has any right to
	   OBJ_array	getAllowedFileOBJs() --> Returns all files that this user has any right to in FileData_OBJ format.  Refer to FileData
			
	   2D_array	     getViewableFileIds() --> Returns all files that this user has any right to
	   2D_array	     getViewableFileOBJs()--> Returns all files that this user has any right to in FileOBJ format  
	   2D_array	     getReadableFileIds() --> Returns all files that this user has any right to
	   2D_array	     getReadableFileOBJs() --> Returns all files that this user has any right to  in FileOBJ format
	   2D_array	     getWriteableFileIds() --> Returns all files that this user has any right to
	   2D_array	     getWriteableFileOBJs() --> Returns all files that this user has any right to in FileOBJ format
	   2D_array	     getAdminableFileIds() --> Returns all files that this user has any right to
	   2D_array	     getAdminableFileOBJs() --> Returns all files that this user has any right to in FileOBJ format
	   Array	     combineArrays($high_priority_array, $low_priority_array) --> for repetition between two arrays, the one in low_priority_array will be deleted.
	   FileDataOBJ_array convertToFileDataOBJ($fid_array) --> convert array of file ids into an array of FileDataOBJs
	}
    */
}