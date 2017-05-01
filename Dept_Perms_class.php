<?php 
/*
Dept_Perms_class.php - Dept_Perms is designed to handle permission settings of each department.
Copyright (C) 2002-2004  Stephen Lawrence, Khoa Nguyen
Copyright (C) 2005-2014 Stephen Lawrence Jr.
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

if (!defined('Dept_Perms_class')) {
    define('Dept_Perms_class', 'true');

    class Dept_Perms extends databaseData
    {
        public $fid;
        public $id;
        public $rights;
        public $file_obj;
        public $error;
        public $chosen_mode;
        protected $connection;
        public $error_flag = false;

        public $NONE_RIGHT = 0;
        public $VIEW_RIGHT = 1;
        public $READ_RIGHT = 2;
        public $WRITE_RIGHT = 3;
        public $ADMIN_RIGHT = 4;
        public $FORBIDDEN_RIGHT = -1;
        public $USER_MODE = 0;
        public $FILE_MODE = 1;

        /**
         * @param int $id
         * @param PDO $connection
         */
        public function Dept_Perms($id, PDO $connection)
        {
            // this can be fid or uid
            $this->id = $id;
            $this->connection = $connection;
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getCurrentViewOnly($limit = true)
        {
            return $this->loadData_UserPerm($this->VIEW_RIGHT, $limit);
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getCurrentNoneRight($limit = true)
        {
            return $this->loadData_UserPerm($this->NONE_RIGHT, $limit);
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getCurrentReadRight($limit = true)
        {
            return $this->loadData_UserPerm($this->READ_RIGHT, $limit);
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getCurrentWriteRight($limit = true)
        {
            return $this->loadData_UserPerm($this->WRITE_RIGHT, $limit);
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getCurrentAdminRight($limit = true)
        {
            return $this->loadData_UserPerm($this->ADMIN_RIGHT, $limit);
        }

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Return a list of files that the department that this OBJ represents has authority >= than $right
         * @param int $right
         * @param bool $limit
         * @return array
         */
        public function loadData_UserPerm($right, $limit = true)
        {
            $limit_query = ($limit) ? "LIMIT {$GLOBALS['CONFIG']['max_query']}" : '';

            $query = "SELECT deptperms.fid
                    FROM
                        {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as data,
                        {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS as deptperms
                    WHERE
                            deptperms.rights >= :right
                    AND
                            deptperms.dept_id = :id
                    AND
                            data.id=deptperms.fid
                    AND
                            data.publishable=1 "
                . $limit_query;
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id,
                ':right' => $right
            ));
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return $result;
        }

        /**
         * return a boolean on whether or not this department
         * has view right to the file whose ID is $data_id*
         * @param int $data_id
         * @return bool
         */
        public function canView($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);

            //check  to see if this department doesn't have a forbidden right or
            //if this file is publishable
            if (!$this->isForbidden($data_id) and $filedata->isPublishable()) {
                // return whether or not this deptartment can view the file
                if ($this->canDept($data_id, $this->VIEW_RIGHT)) {
                    return true;
                } else {
                    false;
                }
            }
            return false;
        }

        /**
         * return a boolean on whether or not this department
         * has read right to the file whose ID is $data_id
         * @param int $data_id
         * @return bool
         */
        public function canRead($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);

            //check  to see if this department doesn't have a forbidden right or
            //if this file is publishable
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                // return whether or not this deptartment can read the file
                if ($this->canDept($data_id, $this->READ_RIGHT) or !$filedata->isPublishable($data_id)) {
                    return true;
                } else {
                    false;
                }
            }
            return false;
        }

        /**
         * return a boolean on whether or not this department
         * has modify right to the file whose ID is $data_id
         * @param int $data_id
         * @return bool
         */
        public function canWrite($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);

            //check  to see if this department doesn't have a forbidden right or
            //if this file is publishable
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                // return whether or not this deptartment can modify the file
                if ($this->canDept($data_id, $this->WRITE_RIGHT)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * return a boolean on whether or not this department
         * has admin right to the file whose ID is $data_id
         * @param int $data_id
         * @return bool
         */
        public function canAdmin($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);

            //check  to see if this department doesn't have a forbidden right or
            //if this file is publishable
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                // return whether or not this deptartment can admin the file
                if ($this->canDept($data_id, $this->ADMIN_RIGHT)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * Return a boolean on whether or not this department has forbidden right to the file whose ID is $data_id
         * @param int $data_id
         * @return bool
         */
        public function isForbidden($data_id)
        {
            $this->error_flag = true; // reset flag
            $query = "
                SELECT
                  rights
                FROM
                  {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS
                WHERE
                  dept_id = :id
                AND
                  fid = :data_id
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':data_id' => $data_id,
                ':id' => $this->id
            ));
            $result = $stmt->fetch();

            if ($stmt->rowCount() == 1) {
                if ($result['rights'] == $this->FORBIDDEN_RIGHT) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $this->error = "Non-unique database entry found in $this->TABLE_DEPT_PERMS";
                $this->error_flag = false;
                return 0;
            }
        }

        /**
         * return a bool on whether or not this department has $right
         * right on file with data id of $data_id
         * @param int $data_id
         * @param int $right
         * @return bool
         */
        public function canDept($data_id, $right)
        {
            $query = "
              SELECT
                *
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS
              WHERE
                dept_id = :id
              AND
                fid = :data_id
              AND
                rights >= :right
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':data_id' => $data_id,
                ':right' => $right,
                ':id' => $this->id
            ));

            $num_results = $stmt->rowCount();
            switch ($num_results) {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default : $this->error = 'non-unique uid: ' . $this->id;
                    break;
            }
        }

        /**
         * Return the numeric permission setting of this department for the file with ID number $data_id
         * @param int $data_id
         * @return int|string
         */
        public function getPermission($data_id)
        {
            $query = "
              SELECT
                rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS
              WHERE
                dept_id = :id
              AND
                fid = :data_id";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':data_id' => $data_id,
                ':id' => $this->id
            ));
            $results = $stmt->fetch();

            $num_results = $stmt->rowCount();
            if ($num_results == 1) {
                $permission = $results['rights'];
                return $permission;
            } elseif ($num_results == 0) {
                return 0;
            } else {
                return 'Non-unique error';
            }
        }
    }
}
