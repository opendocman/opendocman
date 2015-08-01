<?php
/*
FileTypes_class.php - Container for allowed file types info
Copyright (C) 2010-2014 Stephen Lawrence Jr.

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

if (!defined('FileTypes_class')) {
    define('FileTypes_class', 'true', false);

    /**
     * Class that handles the opendocman allowedFileTypes values
     */
    class FileTypes_class
    {
        protected $connection;

        public function FileTypes_class(PDO $pdo)
        {
            $this->connection = $pdo;
        }

       /*
        * Get value for a specific file type based on the key
        * @param string $data
        */
        public function get($data)
        {
        }

        /**
         * Add a new file type
         * @param string $data
         * @return bool
         */
        public function add($data)
        {
            $query = "
              INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}filetypes
                (type, active)
              VALUES
                (:data, '1')
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute(array(':data' => $data['filetype']));

            return true;
        }

        /**
         * Save all the file type info
         * @param array $data Array of values to be saved ($key,$value)
         * @return bool
         */
        public function save($data)
        {
            // First, uncheck all status values
            $query = "
              UPDATE
                {$GLOBALS['CONFIG']['db_prefix']}filetypes
              SET
                active='0'
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();

            if (isset($data['types'])) {
                foreach ($data['types'] as $key => $value) {
                    $query2 = "
                      UPDATE
                        {$GLOBALS['CONFIG']['db_prefix']}filetypes
                      SET
                        active='1'
                      WHERE
                        id = :value
                    ";
                    $stmt = $this->connection->prepare($query2);
                    $stmt->execute(array(':value' => $value));
                }
                return true;
            }
            return false;
        }

        /**
         * Load active file types into a global array
         */
        public function load()
        {
            $GLOBALS['CONFIG']['allowedFileTypes'] = array();
            $query = "
              SELECT
                type
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}filetypes
              WHERE
                active='1'
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                array_push($GLOBALS['CONFIG']['allowedFileTypes'], $row['type']);
            }
        }

        /*
         * Show the file types edit form
        */
        public function edit()
        {
            $filetypes_arr = array();
            $query = "
              SELECT
                *
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}filetypes
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $filetypes_arr[] = $row;
            }

            $GLOBALS['smarty']->assign('filetypes_array', $filetypes_arr);
            display_smarty_template('filetypes.tpl');
        }

        /*
         * Show the form in order to Delete a filetype
        */
        public function deleteSelect()
        {
            $filetypes_arr = array();
            $query = "
              SELECT
                *
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}filetypes
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $filetypes_arr[] = $row;
            }

            $GLOBALS['smarty']->assign('filetypes_array', $filetypes_arr);
            display_smarty_template('filetypes_deleteshow.tpl');
        }

        public function delete($data)
        {
            foreach ($data['types'] as $id) {
                $query = "
                  DELETE FROM
                    {$GLOBALS['CONFIG']['db_prefix']}filetypes
                  WHERE
                    id = :id
                ";
                $stmt = $this->connection->prepare($query);
                $stmt->execute(array(':id' => $id));
            }
            return true;
        }
    }
}
