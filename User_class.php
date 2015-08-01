<?php
/*
User_class.php - Container for user related info
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2013 Stephen Lawrence Jr.

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

if (!defined('User_class')) {
    define('User_class', 'true', false);

    class User extends databaseData
    {
        public $root_id;
        public $id;
        public $username;
        public $first_name;
        public $last_name;
        public $email;
        public $phone;
        public $department;
        public $pw_reset_code;
        public $can_add;
        public $can_checkin;

        /**
         * @param int $id
         * @param PDO $connection
         */
        public function User($id, PDO $connection)
        {
            $this->root_id = $GLOBALS['CONFIG']['root_id'];
            $this->field_name = 'username';
            $this->field_id = 'id';
            $this->tablename = $GLOBALS['CONFIG']['db_prefix'] . $this->TABLE_USER;
            $this->result_limit = 1; //there is only 1 user with a certain user_name or user_id

            databaseData::setTableName($this->TABLE_USER);
            databaseData::databaseData($id, $connection);

            $query = "
                    SELECT 
                        id, 
                        username, 
                        department, 
                        phone, 
                        email, 
                        last_name, 
                        first_name, 
                        pw_reset_code,
                        can_add,
                        can_checkin
                    FROM 
                        {$GLOBALS['CONFIG']['db_prefix']}user 
                    WHERE 
                        id = :id";
            $stmt = $connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetch();

            list(
                    $this->id,
                    $this->username,
                    $this->department,
                    $this->phone,
                    $this->email,
                    $this->last_name,
                    $this->first_name,
                    $this->pw_reset_code,
                    $this->can_add,
                    $this->can_checkin
            ) = $result;
        }

        /**
         * Return department name for current user
         * @return string
         */
        public function getDeptName()
        {
            $query = "
              SELECT
                d.name
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}department d,
                {$GLOBALS['CONFIG']['db_prefix']}user u
              WHERE
                u.id = :id
              AND
                u.department = d.id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetchColumn();

            return $result;
        }

        /**
         * Return department ID for current user
         * @return string
         */
        public function getDeptId()
        {
            return $this->department;
        }

        /**
         * Return an array of publishable documents
         * @return array
         * @param object $publishable
         */
        public function getPublishedData($publishable)
        {
            $data_published = array();
            $index = 0;
            $query = "
              SELECT
                d.id
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}data d,
                {$GLOBALS['CONFIG']['db_prefix']}user u
              WHERE
                d.owner = :id
              AND
                u.id = d.owner
              AND
                d.publishable = :publishable ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':publishable' => $publishable,
                ':id' => $this->id
            ));
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $data_published[$index] = $row;
                $index++;
            }
            return $data_published;
        }

        /**
         * Check whether user from object has Admin rights
         * @return Boolean
         */
        public function isAdmin()
        {
            if ($this->isRoot()) {
                return true;
            }
            $query = "
              SELECT
                admin
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}admin
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() !=1) {
                return false;
            }

            return $result;
        }

        /**
         * Check whether user from object is root
         * @return bool
         */
        public function isRoot()
        {
            return ($this->root_id == $this->getId());
        }

        /**
        * @return boolean
        */
        public function canAdd()
        {
            if ($this->isAdmin()) {
                return true;
            }
            if ($this->can_add) {
                return true;
            }
            return false;
        }
        
        /**
        * @return boolean
        */
        public function canCheckIn()
        {
            if ($this->isAdmin()) {
                return true;
            }
            if ($this->can_checkin) {
                return true;
            }
            return false;
        }

        /**
         * @return string
         */
        public function getPassword()
        {
            $query = "
              SELECT
                password
              FROM
                $this->tablename
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() !=1) {
                header('Location:' . $GLOBALS['CONFIG']['base_url'] . 'error.php?ec=14');
                exit;
            }

            return $result;
        }

        /**
         * @param string $non_encrypted_password
         * @return bool
         */
        public function changePassword($non_encrypted_password)
        {
            $query = "
              UPDATE
                $this->tablename
              SET
                password = md5(:non_encrypted_password)
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':non_encrypted_password' => $non_encrypted_password,
                ':id' => $this->id
            ));
            return true;
        }

        /**
         * @param string $non_encrypted_password
         * @return bool
         */
        public function validatePassword($non_encrypted_password)
        {
            $query = "
              SELECT
                username
              FROM
                $this->tablename
              WHERE
                id = :id
              AND
                password = md5(:non_encrypted_password)
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':non_encrypted_password' => $non_encrypted_password,
                ':id' => $this->id
            ));
            if ($stmt->rowCount() == 1) {
                return true;
            } else {
                // Check the old password() style user password
                $query = "
                  SELECT
                    username
                  FROM
                    $this->tablename
                  WHERE
                    id = :id
                  AND
                    password = password(:non_encrypted_password)
                ";
                $stmt = $this->connection->prepare($query);
                $stmt->execute(array(
                    ':non_encrypted_password' => $non_encrypted_password,
                    ':id' => $this->id
                ));
                if ($stmt->rowCount() == 1) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @param string $new_name
         * @return bool
         */
        public function changeName($new_name)
        {
            $query = "
              UPDATE
                $this->tablename
              SET
                username = :new_name
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':new_name' => $new_name,
                ':id' => $this->id
            ));
            return true;
        }

       /**
        *   Determine if the current user is a reviewer or not
        *   @return boolean
        */
        public function isReviewer()
        {
            // If they are an admin, they can review
            if ($this->isAdmin()) {
                return true;
            }
            
            // Lets see if this non-admin user has a department they can review for, if so, they are a reviewer
            $query = "
            SELECT
              dept_id
            FROM
              {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer
            WHERE
              user_id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            if ($stmt->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        }

       /**
        * Determine if the current user is a reviewer for a specific ID
        * @param int $file_id
        * @return boolean
        */
        public function isReviewerForFile($file_id)
        {
            $query = "SELECT
                            d.id
                      FROM
                            {$GLOBALS['CONFIG']['db_prefix']}data as d,
                            {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer as dr
                      WHERE
                            
                            dr.dept_id = d.department AND
                            dr.user_id = :user_id AND
                            d.department = dr.dept_id AND
                            d.id = :file_id
                            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':user_id' => $this->id,
                ':file_id' => $file_id
            ));

            $num_rows = $stmt->rowCount();
            if ($num_rows < 1) {
                return false;
            }
            return true;
        }

        /**
         * this functions assume that you are an admin thus allowing you to review all departments
         * @return array
         */
        public function getAllRevieweeIds()
        {
            if ($this->isAdmin()) {
                $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE publishable = 0";
                $stmt = $this->connection->prepare($query);
                $stmt->execute(array());
                $result = $stmt->fetchAll();

                $file_data = array();
                $index = 0;
                foreach ($result as $row) {
                    $file_data[$index] = $row[0];
                    $index++;
                }

                return $file_data;
            }
        }
        
        /**
         * getRevieweeIds - Return an array of files that need reviewing under this person
         * @return array
         */
        public function getRevieweeIds()
        {
            if ($this->isReviewer()) {
                // Which departments can this user review?
                $query = "SELECT dept_id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_REVIEWER WHERE user_id = :id";
                $stmt = $this->connection->prepare($query);
                $stmt->execute(array(
                    ':id' => $this->id
                ));
                $result = $stmt->fetchAll();

                $num_depts = $stmt->rowCount();
                $index = 0;
                // Build the query
                $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE (";
                foreach ($result as $row) {
                    $dept = $row['dept_id'];
                    if ($index != $num_depts -1) {
                        $query = $query . " department = :dept OR ";
                    } else {
                        $query = $query . " department = :dept )";
                    }
                    $index++;
                }
                $query = $query . " AND publishable = 0";

                $stmt = $this->connection->prepare($query);
                $stmt->execute(array(':dept' => $dept));
                $result = $stmt->fetchAll();

                $file_data = array();
                $num_files = $stmt->rowCount();

                for ($index = 0; $index< $num_files; $index++) {
                    $fid = $result[$index]['id'];
                    $file_data[$index] = $fid;
                }
                return $file_data;
            }
        }

        /**
         * @return array
         */
        public function getAllRejectedFileIds()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA WHERE publishable = '-1'";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            $file_data = array();
            $num_files = $stmt->rowCount();

            for ($index = 0; $index< $num_files; $index++) {
                list($fid) = $result[$index];
                $file_data[$index] = $fid;
            }
            return $file_data;
        }

        /**
         * @return array
         */
        public function getRejectedFileIds()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE publishable = '-1' and owner = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetchAll();

            $file_data = array();
            $num_files = $stmt->rowCount();

            for ($index = 0; $index< $num_files; $index++) {
                list($fid) = $result[$index];
                $file_data[$index] = $fid;
            }
            return $file_data;
        }

        /**
         * @return array
         */
        public function getExpiredFileIds()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status = -1 AND owner = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetchAll();

            $len = $stmt->rowCount();
            $file_data = array();

            for ($index = 0; $index< $len; $index++) {
                list($fid) = $result[$index];
                $file_data[$index] = $fid;
            }
            return $file_data;
        }

        /**
         * @return int
         */
        public function getNumExpiredFiles()
        {
            $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status =- 1 AND owner = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            return $stmt->rowCount();
        }

        /**
         * @return mixed
         */
        public function getEmailAddress()
        {
            return $this->email;
        }

        /**
         * @return mixed
         */
        public function getPhoneNumber()
        {
            return $this->phone;
        }

        /**
         * /Return full name array where array[0]=firstname and array[1]=lastname
         * @return mixed
         */
        public function getFullName()
        {
            $full_name[0] = $this->first_name;
            $full_name[1] = $this->last_name;

            return $full_name;
        }

        /**
         * Return username of current user
         * @return mixed
         */
        public function getUserName()
        {
            return $this->username;
        }

        /**
         * Return list of checked out files to root
         * @return array
         */
        public function getCheckedOutFiles()
        {
            if ($this->isRoot()) {
                $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE status > 0";
                $stmt = $this->connection->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll();

                $len = $stmt->rowCount();
                $file_data = array();
                for ($index = 0; $index < $len; $index++) {
                    list($fid) = $result[$index];
                    $file_data[$index] = $fid;
                }
                return $file_data;
            }
        }

        /**
         * getAllUsers - Returns an array of all the active users
         * @param $pdo
         * @return array
         */
        public static function getAllUsers($pdo)
        {
            $query = "SELECT id, last_name, first_name FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                $userListArray[] = $row;
            }
            return $userListArray;
        }
    }
}
