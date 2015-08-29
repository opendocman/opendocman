<?php
/*
UserPermission_class.php - relates users to files 
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.
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

if (!defined('UserPermission_class')) {
    define('UserPermission_class', 'true', false);

    class UserPermission extends databaseData
    {
        public $connection;
        public $uid;
        public $user_obj;
        public $user_perms_obj;
        public $dept_perms_obj;
        public $FORBIDDEN_RIGHT;
        public $NONE_RIGHT;
        public $VIEW_RIGHT;
        public $READ_RIGHT;
        public $WRITE_RIGHT;
        public $ADMIN_RIGHT;

        /**
         * @param int $uid
         * @param PDO $connection
         */
        public function UserPermission($uid, PDO $connection)
        {
            $this->uid = $uid;
            $this->connection = $connection;
            $this->user_obj = new User($this->uid, $this->connection);
            $this->user_perms_obj = new User_Perms($this->user_obj->getId(), $connection);
            $this->dept_perms_obj = new Dept_Perms($this->user_obj->getDeptId(), $connection);
            $this->FORBIDDEN_RIGHT = $this->user_perms_obj->FORBIDDEN_RIGHT;
            $this->NONE_RIGHT = $this->user_perms_obj->NONE_RIGHT;
            $this->VIEW_RIGHT = $this->user_perms_obj->VIEW_RIGHT;
            $this->READ_RIGHT = $this->user_perms_obj->READ_RIGHT;
            $this->WRITE_RIGHT = $this->user_perms_obj->WRITE_RIGHT;
            $this->ADMIN_RIGHT = $this->user_perms_obj->ADMIN_RIGHT;
        }

        /**
         * return an array of all the Allowed files ( right >= view_right) ID
         * @param bool $limit
         * @return array
         */
        public function getAllowedFileIds($limit)
        {
            $viewable_array = $this->getViewableFileIds($limit);
            $readable_array = $this->getReadableFileIds($limit);
            $writable_array = $this->getWritableFileIds($limit);
            $adminable_array = $this->getAdminableFileIds($limit);
            $result_array = array_values(array_unique(array_merge($viewable_array, $readable_array, $writable_array, $adminable_array)));
            return $result_array;
        }

        /**
         * return an array of all the Allowed files ( right >= view_right) object
         * @param bool $limit
         * @return array
         */
        public function getAllowedFileOBJs($limit = true)
        {
            return $this->convertToFileDataOBJ($this->getAllowedFileIds($limit));
        }

        /**
         * @param bool $limit
         * @return array
         */
        public function getViewableFileIds($limit = true)
        {
            //These 2 below takes half of the execution time for this function
            $user_perms_file_array = ($this->user_perms_obj->getCurrentViewOnly($limit));
            $dept_perms_file_array = ($this->dept_perms_obj->getCurrentViewOnly($limit));

            $query = "
              SELECT
                up.fid
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA d,
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS up
              WHERE
                (
                  up.uid = :uid
				  AND d.id = up.fid
				  AND up.rights < :view_right
				  AND d.publishable = 1
				  )
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':uid' => $this->uid,
                ':view_right' => $this->VIEW_RIGHT
            ));
            $array = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $dept_perms_file_array = array_diff($dept_perms_file_array, $array);
            $dept_perms_file_array = array_diff($dept_perms_file_array, $user_perms_file_array);
            $total_listing = array_merge($user_perms_file_array, $dept_perms_file_array);
            //$total_listing = array_unique( $total_listing);
            //$result_array = array_values($total_listing);
            return $total_listing;
        }

        /**
         * return an array of all the Allowed files ( right >= view_right) OBJ
         * @param bool $limit
         * @return array
         */
        public function getViewableFileOBJs($limit = true)
        {
            return $this->convertToFileDataOBJ($this->getViewableFileIds($limit));
        }

        /**
         * return an array of all the Allowed files ( right >= read_right) ID
         * @param bool $limit
         * @return array
         */
        public function getReadableFileIds($limit = true)
        {
            $user_perms_file_array = $this->user_perms_obj->getCurrentReadRight($limit);
            $dept_perms_file_array = $this->dept_perms_obj->getCurrentReadRight($limit);
            $published_file_array = $this->user_obj->getPublishedData(1);
            $result_array = array_values(array_unique(array_merge($published_file_array, $user_perms_file_array, $dept_perms_file_array)));
            return $result_array;
        }

        /**
         * return an array of all the Allowed files ( right >= read_right) OBJ
         * @param bool $limit
         * @return array
         */
        public function getReadableFileOBJs($limit = true)
        {
            return $this->convertToFileDataOBJ($this->getReadableFileIds($limit));
        }

        /**
         * return an array of all the Allowed files ( right >= write_right) ID
         * @param bool $limit
         * @return array
         */
        public function getWritableFileIds($limit = true)
        {
            $user_perms_file_array = $this->user_perms_obj->getCurrentWriteRight($limit);
            $dept_perms_file_array = $this->dept_perms_obj->getCurrentWriteRight($limit);
            $published_file_array = $this->user_obj->getPublishedData(1);
            $result_array = array_values(array_unique(array_merge($published_file_array, $user_perms_file_array, $dept_perms_file_array)));
            return $result_array;
        }

        /**
         * return an array of all the Allowed files ( right >= write_right) ID
         * @param bool $limit
         * @return array
         */
        public function getWritableFileOBJs($limit = true)
        {
            return $this->convertToFileDataOBJ($this->getWritableFileIds($limit));
        }

        /**
         * return an array of all the Allowed files ( right >= admin_right) ID
         * @param bool $limit
         * @return array
         */
        public function getAdminableFileIds($limit = true)
        {
            $user_perms_file_array = $this->user_perms_obj->getCurrentAdminRight($limit);
            $dept_perms_file_array = $this->dept_perms_obj->getCurrentAdminRight($limit);
            $published_file_array = $this->user_obj->getPublishedData(1);
            $result_array = array_values(array_unique(array_merge($published_file_array, $user_perms_file_array, $dept_perms_file_array)));
            return $result_array;
        }

        /**
         * return an array of all the Allowed files ( right >= admin_right) OBJ
         * @param bool $limit
         * @return array
         */
        public function getAdminableFileOBJs($limit = true)
        {
            return $this->convertToFileDataOBJ($this->getAdminableFileIds($limit));
        }

        /**
         * Combine a high priority array with a low priority array
         * @param array $high_priority_array
         * @param array $low_priority_array
         * @return array
         */
        public function combineArrays($high_priority_array, $low_priority_array)
        {
            return databaseData::combineArrays($high_priority_array, $low_priority_array);
        }

        /**
         * getAuthority
         * Return the authority that this user have on file data_id
         * by combining and prioritizing user and department right
         * @param int $data_id
         * @return int
         */
        public function getAuthority($data_id)
        {
            $data_id = (int) $data_id;
            $fileData = new FileData($data_id, $this->connection);

            if ($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id)) {
                return $this->ADMIN_RIGHT;
            }

            if ($fileData->isOwner($this->uid) && $fileData->isLocked()) {
                return $this->WRITE_RIGHT;
            }

            $user_permissions = $this->user_perms_obj->getPermission($data_id);
            $department_permissions = $this->dept_perms_obj->getPermission($data_id);

            if ($user_permissions >= $this->user_perms_obj->NONE_RIGHT and $user_permissions <= $this->user_perms_obj->ADMIN_RIGHT) {
                return $user_permissions;
            } else {
                return $department_permissions;
            }
        }
    }
}
