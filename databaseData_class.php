<?php
/*
databaseData_class.php - sets up database schema and provides various db functions
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.

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

if (!defined("databaseData_class"));
{
    define("databaseData_class", "true", false);
    
    //DO NOT INSTANTIATE THIS ABSTRACT CLASS
    class databaseData
    {
        public $DB_PREFIX;
        public $TABLE_ADMIN = 'admin';
        public $TABLE_CATEGORY = 'category';
        public $TABLE_DATA = 'data';
        public $TABLE_DEPARTMENT = 'department';
        public $TABLE_DEPT_PERMS = 'dept_perms';
        public $TABLE_DEPT_REVIEWER = 'dept_reviewer';
        public $TABLE_LOG = 'log';
        public $TABLE_RIGHTS = 'rights';
        public $TABLE_USER = 'user';
        public $TABLE_USER_PERMS = 'user_perms';
        public $FORBIDDEN_RIGHT = -1;
        public $NONE_RIGHT = 0;
        public $VIEW_RIGHT = 1;
        public $READ_RIGHT = 2;
        public $WRITE_RIGHT = 3;
        public $ADMIN_RIGHT = 4;
        public $name;
        public $id;
        protected $connection;
        public $tablename;
        public $error;
        public $field_name;
        public $field_id;
        public $result_limit;

        /**
         * @param int $id
         * @param PDO $connection
         */
        public function databaseData($id, PDO $connection)
        {
            $this->connection = $connection;
            $this->setId($id); //setId not only set the $id data member but also find and set name
            $this->result_limit = 1; //expect unique data fields on default
        }

        /**
         * @param string $table_name
         */
        public function setTableName($table_name)
        {
            $this->tablename = "$table_name";
        }

        /**
         * sets the data member $id and it also look a name
         * that is correspondent to that id and set it to
         * the data member field $name
         * @param int $id
         */
        public function setId($id)
        {
            $this->id = (int) $id;
            $this->name = $this->findName();
        }

        /**
         * can only be used under the assumption that
         * the name field in the DB is unique, e.g. username
         * @param string $name
         */
        public function setName($name)
        {
            $this->name = $name;
            $this->id = findId();
        }

        /**
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return int
         */
        public function findId()
        {
            $query = "
              SELECT
                $this->field_id
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}{$this->tablename}
              WHERE
                $this->field_name = :name
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':name' => $this->name
            ));
            $result = $stmt->fetchAll();
            $row_count = $stmt->rowCount();

            if ($row_count > $this->result_limit and result_limit != 'UNLIMITED') {
                /*if the result is more than expected error var is set*/
                $this->error='Error: non-unique';
            } elseif ($row_count == 0) {
                // record must exist.  Error message is stored
                $this->error = 'Error: unable to fine id in database';
            } else {
                $id = $result[0][0];
            }
            return $id;
        }

        /**
         * logic in findName() is simular to findId().  Please look at findId()'s
         * comments if you need help with this function
         * @return string
         */
        public function findName()
        {
            $name = '';
            $query = "SELECT
                        $this->field_name
                      FROM
                        {$GLOBALS['CONFIG']['db_prefix']}$this->tablename
                      WHERE
                        $this->field_id = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetchAll();
            $row_count = $stmt->rowCount();

            if ($row_count > $this->result_limit and result_limit != 'UNLIMITED') {
                $this->error='Error: non-unique';
            } elseif ($row_count == 0) {
                $this->error = 'Error: unable to find id in database';
            } else {
                $name = $result[0][0];
            }
            return $name;
        }

        /**
         * assuming that userid will never change
         */
        public function reloadData()
        {
            //Since all the data are set at the time when $id or $name
            //is set.  If another program access the DB and changes any
            //information, this OBJ will no longer contain up-to-date
            //information.  reloadData() will reload all the data
            $this->setId($this->id);
        }

        /**
         * @return mixed
         */
        public function getError()
        {
            /* Get error will return the last thrown error */
            return $this->error;
        }

        /**
         * combineArrays() uses a linear search algorithm with the
         * cost of n*n, n being the size of the biggest array.  combineArrays()
         * gives $high_priority_array the advantage by merging the
         * low_priority_array onto it.  One can look at these two arrays
         * as 2 sets and combineArrays acts as a union operator.
         * For briefness, let's $high = $high_priority_array and
         * $low = $low_priority_array
         * @param array $high_priority_array
         * @param array $low_priority_array
         * @return array
         */
        public function combineArrays($high_priority_array, $low_priority_array)
        {
            $found = false;
            $result_array = array();
            $result_array = $high_priority_array; //$high is being kept
            $result_array_index = sizeof($high_priority_array);
            //iterate through $low
            for ($l = 0 ; $l<sizeof($low_priority_array); $l++) {
                //each $low element will be compared with
                //every $high element
                for ($r = 0; $r<sizeof($result_array); $r++) {
                    if ($result_array[$r] == $low_priority_array[$l]) {
                        //if a $low element is already in the
                        //$high array, it is ignored
                        $r = sizeof($result_array);
                        $found = true;
                    }
                }

                //if certain $low element is not found in $high, it
                //will be append to the back of high
                if (!$found) {
                    $result_array[$result_array_index++] = $low_priority_array[$l];
                }
                $found = false;
            }
            return $result_array;
        }

        /**
         * @param array $fid_array
         * @return array
         */
        public function convertToFileDataOBJ($fid_array)
        {
            $file_data_array = array();
            for ($i = 0; $i<sizeof($fid_array); $i++) {
                $file_data_array[$i] = new FileData($fid_array[$i], $this->connection);
            }
            return $file_data_array;
        }
    }
}
