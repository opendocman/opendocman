<?php
use Aura\Html\Escaper as e;

/**
FileData_class.php - Builds file data objects
Copyright (C) 2002-2004 Stephen Lawrence Jr., Khoa Nguyen
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

if (!defined('FileData_class')) {
    define('FileData_class', 'true', false);

    /*
          mysql> describe data;
          +-------------------+----------------------+------+-----+---------------------+----------------+
          | id                | smallint(5) unsigned |      | PRI | NULL                | auto_increment |
          | category          | tinyint(4) unsigned  |      |     | 0                   |                |
          | owner             | tinyint(4) unsigned  |      |     | 0                   |                |
          | realname          | varchar(255)         |      |     |                     |                |
          | created           | datetime             |      |     | 0000-00-00 00:00:00 |                |
          | description       | varchar(255)         | YES  |     | NULL                |                |
          | comment           | varchar(255)         |      |     |                     |                |
          | status            | tinyint(4) unsigned  |      |     | 0                   |                |
          | department        | tinyint(4)           |      |     | 0                   |                |
          | default_rights    | int(4)               | YES  |     | NULL                |                |
          | publishable       | int(4)               | YES  |     | NULL                |                |
          | reviewer          | int(4)               | YES  |     | NULL                |                |
          | reviewer_comments | varchar(255)         | YES  |     | NULL                |                |
          +-------------------+----------------------+------+-----+---------------------+----------------+
    */

    class FileData extends databaseData
    {
        public $category;
        public $owner;
        public $created_date;
        public $description;
        public $comment;
        public $status;
        public $department;
        public $default_rights;
        public $view_users;
        public $read_users;
        public $write_users;
        public $admin_users;
        public $filesize;
        public $isLocked;
        protected $connection;

        public function FileData($id, $connection)
        {
            $this->field_name = 'realname';
            $this->field_id = 'id';
            $this->result_limit = 1;  //EVERY FILE IS LISTED UNIQUELY ON THE DATABASE DATA;
            $this->tablename = $this->TABLE_DATA;
            $this->connection = $connection;
            databaseData::databaseData($id, $connection);

            $this->loadData();
        }

        /**
         * Return a boolean whether this file exists
         * @return bool|string
         */
        public function exists()
        {
            $query = "
              SELECT
                *
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->tablename
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));

            switch ($stmt->rowCount()) {
                case 1: return true;
                    break;
                case 0: return false;
                    break;
                default: $this->error = 'Non-unique';
                    return $this->error;
                    break;
            }
        }

        /**
         * This is a more complex version of base class's loadData.
         * This function loads up all the fields in data table
         */
        public function loadData()
        {
            $query = "
              SELECT
                category,
                owner,
                created,
                description,
                comment,
                status,
                department,
                default_rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->tablename
              WHERE
                id = :id
                ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchAll();

            if ($stmt->rowCount() == $this->result_limit) {
                foreach ($result as $row) {
                    $this->category = $row['category'];
                    $this->owner = $row['owner'];
                    $this->created_date = $row['created'];
                    $this->description = stripslashes($row['description']);
                    $this->comment = stripslashes($row['comment']);
                    $this->status = $row['status'];
                    $this->department = $row['department'];
                    $this->default_rights = $row['default_rights'];
                }
            } else {
                $this->error = 'Non unique file id';
            }
            $this->isLocked = $this->status == -1;
        }

        /**
         * Update the dynamic values of the file
         */
        public function updateData()
        {
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                category = :category,
                owner = :owner,
                description = :description,
                comment = :comment,
                status = :status,
                department = :department,
                default_rights = :default_rights
               WHERE
                id = :id
            ";

            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':category' => $this->category,
                ':owner' => $this->owner,
                ':description' => $this->description,
                ':comment' => $this->comment,
                ':status' => $this->status,
                ':department' => $this->department,
                ':default_rights' => $this->default_rights,
                ':id' => $this->id
            ));
        }

        /**
         * return filesize
         * @return mixed
         */
        public function getFileSize()
        {
            return $this->filesize;
        }

        /**
         * return this file's category id
         * @return int
         */
        public function getCategory()
        {
            return $this->category;
        }

        /**
         * @param int $value
         */
        public function setCategory($value)
        {
            $this->category = $value;
        }

        /**
         * return this file's category name
         * @return string
         */
        public function getCategoryName()
        {
            $query = "
              SELECT
                name
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_CATEGORY
              WHERE
                id = :category_id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':category_id' => $this->category));
            $result = $stmt->fetch();

            $name = $result['name'];

            return $name;
        }

        /**
         * return a boolean on whether the user ID $uid is the owner of this file
         * @param int $uid
         * @return bool
         */
        public function isOwner($uid)
        {
            return ($this->getOwner() == $uid);
        }

        /**
         * return the ID of the owner of this file
         * @return int
         */
        public function getOwner()
        {
            return $this->owner;
        }

        /**
         * set the user_id of the file
         * @param int $value
         */
        public function setOwner($value)
        {
            $this->owner = $value;
        }

        /**
         * return the username of the owner
         * @return mixed
         */
        public function getOwnerName()
        {
            $user_obj = new User($this->owner, $this->connection);
            return $user_obj->getName();
        }

        /**
         * return owner's full name in an array where index=0 corresponds to the last name
         * and index=1 corresponds to the first name
         * @return mixed
         */
        public function getOwnerFullName()
        {
            $user_obj = new User($this->owner, $this->connection);
            return $user_obj->getFullName();
        }

        /**
         * return the owner's dept ID.  Often, this is also the department of the file.
         * if the owner changes his/her department after he/she changes department, then
         * the file's department will not be the same as it's owner's.
         * @return string
         */
        public function getOwnerDeptId()
        {
            $user_obj = new User($this->getOwner(), $this->connection);
            return $user_obj->getDeptId();
        }

        /**
         * This function serve the same purpose as getOwnerDeptId() except that it returns
         * the department name instead of department id
         * @return string
         */
        public function getOwnerDeptName()
        {
            $user_obj = new User($this->getOwner(), $this->connection);
            return $user_obj->getDeptName();
        }

        /**
         * return file description
         * @return string
         */
        public function getDescription()
        {
            return $this->description;
        }

        /**
         * @param string $value
         */
        public function setDescription($value)
        {
            $this->description = $value;
        }

        /**
         * @return int
         */
        public function getDefaultRights()
        {
            return $this->default_rights;
        }

        /**
         * @param int $value
         */
        public function setDefaultRights($value)
        {
            $this->default_rights = $value;
        }

        /**
         * return file commnents
         * @return mixed
         */
        public function getComment()
        {
            return $this->comment;
        }

        /**
         * @param string $value
         */
        public function setComment($value)
        {
            $this->comment = $value;
        }

        /**
         * return the status of the file
         * @return int
         */
        public function getStatus()
        {
            return $this->status;
        }

        /**
         * @param int $status Status of file
         */
        public function setStatus($status)
        {
            $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA set status = :status where id = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':status' => $status,
                ':id' => $this->id
            ));
        }

        /**
         * return a User OBJ of the person who checked out this file
         * @return User
         */
        public function getCheckerOBJ()
        {
            $user = new User($this->status, $this->connection);
            return $user;
        }

        /**
         * return the department ID of the file
         * @return int
         */
        public function getDepartment()
        {
            return $this->department;
        }

        /**
         * @param int $value
         */
        public function setDepartment($value)
        {
            $this->department = $value;
        }

        /**
         * return the name of the department of the file
         * @return string
         */
        public function getDeptName()
        {
            $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPARTMENT WHERE id = :department_id";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':department_id' => $this->getDepartment()));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() == 0) {
                echo('ERROR: No database entry exists in department table for ID = '. e::h($this->getDepartment()) .'.');
                return "ERROR";
                //exit;
            }

            return $result;
        }

        /**
         * return the date that the file was created
         * @return string
         */
        public function getCreatedDate()
        {
            return $this->created_date;
        }

        /**
         * return the latest modifying date on the file
         * @return string
         */
        public function getModifiedDate()
        {
            $query = "SELECT modified_on FROM {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_LOG WHERE id = :id ORDER BY modified_on DESC limit 1";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetch();
            $name = $result['modified_on'];

            return $name;
        }

        /**
         * return the realname of the file
         * @return string
         */
        public function getRealName()
        {
            return databaseData::getName();
        }

        /**
         * Return the dept rights on this file for a given department
         * @param int $dept_id
         * @return int
         */
        public function getDeptRights($dept_id)
        {
            $query = "
              SELECT
                rights
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DEPT_PERMS
			  WHERE
			    fid = :fid
	  		  AND
	  		    dept_id = :dept_id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':fid' => $this->id,
                ':dept_id' => $dept_id
            ));
            $result = $stmt->fetchColumn();

            return $result;
        }

        /**
         * convert an array of user id into an array of user objects
         * @param array $uid_array
         * @return array
         */
        public function toUserOBJs($uid_array)
        {
            $UserOBJ_array = array();
            for ($i = 0; $i<sizeof($uid_array); $i++) {
                $UserOBJ_array[$i] = new User($uid_array[$i], $this->connection);
            }
            return $UserOBJ_array;
        }

        /**
         * Return a boolean on whether or not this file is publishable
         * @return string
         */
        public function isPublishable()
        {
            $query = "
              SELECT
                publishable
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() != 1) {
                echo('DB error.  Unable to locate file id ' . e::h($this->id) . ' in table '.$GLOBALS['CONFIG']['db_prefix'].'data.  Please contact ' . $GLOBALS['CONFIG']['site_mail'] . ' for help');
                exit;
            }

            return $result;
        }

        /**
         * @return bool
         */
        public function isArchived()
        {
            $query = "
              SELECT
                publishable
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchColumn();

            if ($stmt->rowCount() != 1) {
                echo('DB error.  Unable to locate file id ' . e::h($this->id) . ' in table '.$GLOBALS['CONFIG']['db_prefix'].'data.  Please contact ' . $GLOBALS['CONFIG']['site_mail'] . ' for help');
                exit;
            }

            return ($result == 2);
        }

        /**
         * This function sets the publishable field in the data table to $boolean
         * @param bool $boolean
         */
        public function Publishable($boolean = true)
        {
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                publishable = :boolean,
                reviewer = :uid
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':boolean' => $boolean,
                ':uid' => $_SESSION['uid'],
                ':id' => $this->id
            ));
        }

        /**
         * return the user id of the reviewer
         * @return int
         */
        public function getReviewerID()
        {
            $query = "
              SELECT
                reviewer
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchColumn();

            return $result;
        }

        /**
         * return the username of the reviewer
         * @return bool
         */
        public function getReviewerName()
        {
            $reviewer_id = $this->getReviewerID();
            if (isset($reviewer_id)) {
                $user_obj = new User($reviewer_id, $this->connection);
                return $user_obj->getName();
            }
            return false;
        }

        /**
         * Set $comments into the reviewer comment field in the DB
         * @param $comments
         */
        public function setReviewerComments($comments)
        {
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                reviewer_comments = :comments
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(
                ':comments' => $comments,
                ':id' => $this->id
            ));
        }


        /**
         * Return the reviewers' comment toward this file
         * @return string
         */
        public function getReviewerComments()
        {
            $query = "
              SELECT
                reviewer_comments
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
            $result = $stmt->fetchColumn();

            return $result;
        }

        /**
         *
         */
        public function temp_delete()
        {
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                publishable = 2
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
        }

        /**
         *
         */
        public function undelete()
        {
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}$this->TABLE_DATA
              SET
                publishable = 0
              WHERE
                id = :id
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':id' => $this->id));
        }

        /**
         * @return bool
         */
        public function isLocked()
        {
            return $this->isLocked;
        }
    }
}
