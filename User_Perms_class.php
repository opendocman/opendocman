<?php
/*
User_Perms_class.php - relates users to specific files
Copyright (C) 2002-2013 Stephen Lawrence Jr.

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

if (!defined('User_Perms_class')) {
    define('User_Perms_class', 'true', false);

    class User_Perms extends databaseData
    {
        public $fid;
        public $id;
        public $rights;
        public $user_obj;
        public $dept_perms_obj;
        public $file_obj;
        public $error;
        public $chosen_mode;
        public $connection;

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
        public function User_Perms($id, PDO $connection)
        {
            $this->id = $id;  // this can be fid or uid
            $this->user_obj = new User($id, $connection);
            $this->dept_perms_obj = new Dept_Perms($this->user_obj->GetDeptId(), $connection);
            $this->connection = $connection;
        }

        /**
         * return an array of user whose permission is >= view_right
         * @param bool $limit
         * @return array
         */
        public function getCurrentViewOnly($limit = true)
        {
            return $this->loadData_UserPerm($this->VIEW_RIGHT, $limit);
        }

        /**
         * return an array of user whose permission is >= none_right
         * @param bool $limit
         * @return array
         */
        public function getCurrentNoneRight($limit = true)
        {
            return $this->loadData_UserPerm($this->NONE_RIGHT, $limit);
        }

        /**
         * return an array of user whose permission is >= read_right
         * @param bool $limit
         * @return array
         */
        public function getCurrentReadRight($limit = true)
        {
            return $this->loadData_UserPerm($this->READ_RIGHT, $limit);
        }

        /**
         * return an array of user whose permission is >= write_right
         * @param bool $limit
         * @return array
         */
        public function getCurrentWriteRight($limit = true)
        {
            return $this->loadData_UserPerm($this->WRITE_RIGHT, $limit);
        }

        /**
         * return an array of user whose permission is >= admin_right
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
         * All of the functions above provide an abstraction for loadData_UserPerm($right).
         * If your user does not want to or does not know the numeric value for permission,
         * use the function above.  LoadData_UserPerm($right) can be invoke directly.
         * @param integer $right The "Right" that is being checked.
         * @param integer $right The permissions level you are checking for
         * @param boolean $limit boolean Should we limit the query to max_query size?
         * @return array
         */
        public function loadData_UserPerm($right, $limit)
        {
            $limit_query = ($limit) ? "LIMIT {$GLOBALS['CONFIG']['max_query']}" : '';

            if ($this->user_obj->isAdmin()) {
                $query = "SELECT d.id
                        FROM
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d
                        WHERE
                            d.publishable = 1 "
                                    . $limit_query;
                $stmt =  $this->connection->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll();
            } elseif ($this->user_obj->isReviewer()) {
                // If they are a reviewer, let them see files in all departments they are a reviewer for
                $query = "SELECT d.id
                        FROM
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d,
                            {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_REVIEWER as dr
                        WHERE
                            d.publishable = 1
                        AND
                            dr.dept_id = d.department
                        AND
                            dr.user_id = :id "
                                    . $limit_query;
                $stmt =  $this->connection->prepare($query);
                $stmt->execute(array(
                    ':id' => $this->id
                ));
                $result = $stmt->fetchAll();
            } else {
                //Select fid, owner_id, owner_name of the file that user-->$id has rights >= $right
                $query = "
                  SELECT
                    up.fid
                  FROM
                    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA as d,
                    {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS as up
                  WHERE (
                    up.uid = :id
				    AND
                    d.id = up.fid
                    AND
                    up.rights >= :right
                    AND
                    d.publishable = 1
                  ) $limit_query";
                $stmt =  $this->connection->prepare($query);
                $stmt->execute(array(
                    ':right' => $right,
                    ':id' => $this->id
                ));
                $result = $stmt->fetchAll();
            }

            $index = 0;
            $fileid_array = array();
            //$fileid_array[$index][0] ==> fid
            //$fileid_array[$index][1] ==> owner
            //$fileid_array[$index][2] ==> username
            $llen = $stmt->rowCount();
            while ($index < $llen) {
                list($fileid_array[$index]) = $result[$index];
                $index++;
            }
            return $fileid_array;
        }

        /**
         * return whether if this user can view $data_id
         * @param int $data_id
         * @return bool
         */
        public function canView($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                if ($this->canUser($data_id, $this->VIEW_RIGHT) or $this->dept_perms_obj->canView($data_id)or $this->canAdmin($data_id)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * return whether if this user can read $data_id
         * @param $data_id
         * @return bool
         */
        public function canRead($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);
            if (!$this->isForbidden($data_id) or !$filedata->i->isPublishable()) {
                if ($this->canUser($data_id, $this->READ_RIGHT) or $this->dept_perms_obj->canRead($data_id) or $this->canAdmin($data_id)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * return whether if this user can modify $data_id
         * @param $data_id
         * @return bool
         */
        public function canWrite($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                if ($this->canUser($data_id, $this->WRITE_RIGHT) or $this->dept_perms_obj->canWrite($data_id) or $this->canAdmin($data_id)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * return whether if this user can admin $data_id
         * @param $data_id
         * @return bool
         */
        public function canAdmin($data_id)
        {
            $filedata = new FileData($data_id, $this->connection);
            if (!$this->isForbidden($data_id) or !$filedata->isPublishable()) {
                if ($this->canUser($data_id, $this->ADMIN_RIGHT) or $this->dept_perms_obj->canAdmin($data_id) or $filedata->isOwner($this->id)) {
                    return true;
                } else {
                    false;
                }
            }
        }

        /**
         * return whether if this user is forbidden to have acc
         * @param $data_id
         * @return bool
         */
        public function isForbidden($data_id)
        {
            $query = "
              SELECT
                rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS
              WHERE
                uid = :id
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':id' => $this->id
            ));
            $result = $stmt->fetch();

            if ($stmt->rowCount() == 1) {
                list($right) = $result[0];
                if ($right == $this->FORBIDDEN_RIGHT) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        
        /**
         * This function is used by all the canRead, canView, etc... abstract functions.
         * Users may invoke this function directly if they are familiar of the numeric permision values.
         * If they are an "Admin" or "Reviewer" for this file return true right away
         * @param integer $data_id The ID number of the file in question
         * @param integer $right The number of the "right" ID that is being checked
         * @return true They CAN perform the right
         */
        public function canUser($data_id, $right)
        {
            if ($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id)) {
                return true;
            }
            $query = "
              SELECT
                *
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS
              WHERE
                uid = :id
              AND
                fid = :data_id
              AND
                rights >= :right
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':right' => $right,
                ':data_id' => $data_id,
                ':id' => $this->id
            ));


            switch ($stmt->rowCount()) {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default : $this->error = "non-unique uid: $this->id";
                    break;
            }
        }

        /**
         * return this user's permission on the file $data_id
         * @param int $data_id
         * @return int|string
         */
        public function getPermission($data_id)
        {
            if ($GLOBALS['CONFIG']['root_id'] == $this->user_obj->getId()) {
                return 4;
            }

            $query = "
              SELECT
                rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS
              WHERE
                uid = :id
              AND
                fid = :data_id
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':data_id' => $data_id,
                ':id' => $this->id
            ));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() == 1) {
                return $result;
            } elseif ($stmt->rowCount() == 0) {
                return -999;
            }
        }

        /**
         * @param int $user_id
         * @param int $data_id
         * @return string
         */
        public function getPermissionForUser($user_id, $data_id)
        {
            $query = "
              SELECT
                rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}user_perms
              WHERE
                uid = :user_id
              AND
                fid = :data_id
            ";
            $stmt =  $this->connection->prepare($query);
            $stmt->execute(array(
                ':user_id' => $user_id,
                ':data_id' => $data_id
            ));
            $result = $stmt->fetchColumn();

            return $result;
        }
    }
}
