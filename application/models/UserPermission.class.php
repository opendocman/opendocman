<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Relates users to files

if (!defined('UserPermission_class')) {
    define('UserPermission_class', 'true', false);

    class UserPermission extends databaseData
    {
        public $connection;
        public $uid;
        public $user_obj;
        public $user_perms_obj;
        public $dept_perms_obj;
        public $category_perms_obj;
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
        public function __construct($uid, PDO $connection)
        {
            $this->uid = $uid;
            $this->connection = $connection;
            
            // Validate UID before proceeding
            if (empty($uid) || !is_numeric($uid)) {
                throw new Exception("Invalid UID provided to UserPermission constructor: " . var_export($uid, true));
            }
            
            try {
                $this->user_obj = new User($this->uid, $this->connection);
            } catch (Exception $e) {
                error_log("UserPermission constructor - Failed to create User object: " . $e->getMessage());
                throw new Exception("Failed to create User object for UID: " . $uid . " - " . $e->getMessage());
            }
            
            // Check if user object was created successfully and user exists
            if ($this->user_obj === null) {
                throw new Exception("User object is null for UID: " . $uid);
            }
            
            if (!empty($this->user_obj->error)) {
                error_log("UserPermission constructor - User object has error: " . $this->user_obj->error);
                throw new Exception("User object error for UID: " . $uid . " - " . $this->user_obj->error);
            }
            
            // Ensure user ID is valid before creating dependent objects
            $userId = $this->user_obj->getId();
            if (empty($userId)) {
                throw new Exception("User object has no valid ID for UID: " . $uid);
            }
            
            // Create User_Perms object
            $this->user_perms_obj = new User_Perms($userId, $connection, $this->user_obj);
            
            if ($this->user_perms_obj === null) {
                throw new Exception("User_Perms object is null after creation");
            }
            
            // Create Dept_Perms object
            $deptId = $this->user_obj->getDeptId();
            $this->dept_perms_obj = new Dept_Perms($deptId, $connection, $this->user_obj);
            
            if ($this->dept_perms_obj === null) {
                throw new Exception("Dept_Perms object is null");
            }

            // Create CategoryPerms object
            $this->category_perms_obj = new CategoryPerms($this->connection);

            if ($this->category_perms_obj === null) {
                throw new Exception("CategoryPerms object is null");
            }
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

            // Files inherited from the file's category permission template
            // (category user or dept grant). The helper already excludes files
            // with any doc-level user_perms row, mirroring getAuthority().
            $category_file_array = $this->getCategoryFileIds($this->VIEW_RIGHT, $limit);
            $category_file_array = array_diff($category_file_array, $array);
            $category_file_array = array_diff($category_file_array, $user_perms_file_array);
            $category_file_array = array_diff($category_file_array, $dept_perms_file_array);

            $total_listing = array_merge($user_perms_file_array, $dept_perms_file_array, $category_file_array);
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
            $category_file_array = $this->getCategoryFileIds($this->READ_RIGHT, $limit);
            $result_array = array_values(array_unique(array_merge($published_file_array, $user_perms_file_array, $dept_perms_file_array, $category_file_array)));
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
         * Return the IDs of publishable files the user can access at or above
         * $right through their category permission template ("live inheritance").
         *
         * Both the category-user and the category-dept channel are considered.
         * A category-user row always takes priority over the category-dept row
         * for the same file (mirroring getAuthority()). Files that have any
         * doc-level user_perms row are excluded because such a row is
         * authoritative and supersedes inheritance.
         *
         * @param int $right
         * @param bool $limit
         * @return array
         */
        protected function getCategoryFileIds($right, $limit = true)
        {
            $limit_query = ($limit) ? "LIMIT {$GLOBALS['CONFIG']['max_query']}" : '';
            $deptId = $this->user_obj->getDeptId();
            $query = "
                SELECT d.id
                FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA d
                JOIN {$GLOBALS['CONFIG']['db_prefix']}category_perms cp ON cp.cat_id = d.category
                WHERE d.publishable = 1
                  AND NOT EXISTS (
                      SELECT 1 FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_USER_PERMS up
                      WHERE up.uid = :uid AND up.fid = d.id
                  )
                  AND (
                    (cp.user_id = :uid AND cp.rights >= :right)
                    OR
                    (cp.dept_id = :dept AND cp.rights >= :right
                     AND NOT EXISTS (
                         SELECT 1 FROM {$GLOBALS['CONFIG']['db_prefix']}category_perms cp2
                         WHERE cp2.cat_id = d.category AND cp2.user_id = :uid
                     ))
                  )
                $limit_query
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':uid' => $this->uid,
                ':dept' => $deptId,
                ':right' => $right,
            ));

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

            // Add null check for user_obj before calling methods
            if ($this->user_obj === null) {
                error_log("UserPermission::getAuthority - user_obj is null for UID: " . $this->uid);
                return $this->FORBIDDEN_RIGHT;
            }

            if ($this->user_obj->isAdmin() || $this->user_obj->isReviewerForFile($data_id)) {
                return $this->ADMIN_RIGHT;
            }

            if ($fileData->isOwner($this->uid) && $fileData->isLocked()) {
                return $this->WRITE_RIGHT;
            }

            $user_permissions = $this->user_perms_obj->getPermission($data_id);
            $department_permissions = $this->dept_perms_obj->getPermission($data_id);

            // A doc-level user_perms row is authoritative whenever it exists:
            // 1-4 = grant, 0 ("Unset") = explicit no access, -1 = forbidden.
            // No row is signalled by -999 (see User_Perms::getPermission).
            if ($user_permissions != -999) {
                return $user_permissions;
            }

            // A department grant only applies when it is a real positive grant.
            // Dept_Perms::getPermission() returns 0 both for "no row" and for
            // legacy files that store a rights=0 row for every department, so a
            // value of 0 (or -1) must fall through to the category template
            // instead of short-circuiting inheritance.
            if ($department_permissions > 0) {
                return $department_permissions;
            }

            // Category fallback
            $catId = $fileData->getCategory();
            if ($catId > 0) {
                $catUserPerm = $this->category_perms_obj->getPermission($catId, $this->uid, null);
                if ($catUserPerm !== null) {
                    return $catUserPerm;
                }
                $catDeptPerm = $this->category_perms_obj->getPermission($catId, null, $this->user_obj->getDeptId());
                if ($catDeptPerm !== null) {
                    return $catDeptPerm;
                }
            }

            return 0;
        }
    }
}
