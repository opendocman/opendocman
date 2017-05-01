<?php
/*
Settings_class.php - Container for settings related info
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

if (!defined('Settings_class')) {
    define('Settings_class', 'true', false);

    /**
     * Class that handles the opendocman settings values
     *
     * @author Stephen J. Lawrence Jr.
     */
    class Settings
    {
        protected $connection;
        
        public function Settings(PDO $pdo)
        {
            $this->connection = $pdo;
        }

       /**
        * Get value for a specific setting based on the key
        * @param string $key
        */
        public function get($key)
        {
        }

       /**
        * Save all the settings
        * @param array $data Array of values to be saved ($key,$value)
        * @return bool
        */
        public function save($data)
        {
            foreach ($data as $key=>$value) {
                $query = "
                  UPDATE
                    {$GLOBALS['CONFIG']['db_prefix']}settings
                  SET VALUE = :value
                  WHERE
                    name = :key
                ";
                $stmt = $this->connection->prepare($query);
                $stmt->execute(array(
                    ':value' => $value,
                    ':key' => $key
                ));
            }
            return true;
        }
        /**
        * Load settings to an array
        * return array
        */
        public function load()
        {
            $query = "
              SELECT
                name,
                value
            FROM
              {$GLOBALS['CONFIG']['db_prefix']}settings
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            foreach ($result as $row) {
                $GLOBALS['CONFIG'][$row['name']] = $row['value'];
            }
        }

        /**
         * Show the settings edit form
         */
        public function edit()
        {
            $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}settings";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            $GLOBALS['smarty']->assign('themes', $this->getThemes());
            $GLOBALS['smarty']->assign('languages', $this->getLanguages());
            $GLOBALS['smarty']->assign('useridnums', $this->getUserIdNums());
            $GLOBALS['smarty']->assign('settings_array', $result);
            display_smarty_template('settings.tpl');
        }

        /**
         * Validate a specific setting based on its validation type
         * @param string $key The name of the setting to be tested
         * @param string $value The value of the setting to be tested
         */
        public function validate($key, $value)
        {
            // NOT IMPLEMENTED
        }

        /**
         * This function will return an array of the possible theme names found in the /templates folder
         * for use in the settings form
         */
        public function getThemes()
        {
            $themes = $this->getFolders(ABSPATH . 'templates');
            return $themes;
        }

        /**
         * @return mixed
         */
        public function getLanguages()
        {
            $languages = $this->getFolders(ABSPATH . 'includes/language');
            return str_replace('.php', '', $languages);
        }

        /**
         * @param string $path
         * @return array
         */
        public function getFolders($path = '.')
        {
            $file_list=array();
            if ($handle = opendir($path)) {
                while (false !== ($file = readdir($handle))) {
                    // Filter out any other types of folders that might be in here
                    if ($file != 'layouts' && $file != 'views' && $file != "." && $file != ".." && $file != ".svn" && $file != 'README' && $file != 'sync.sh' && $file != 'common' && $file != 'DataTables') {
                        array_push($file_list, $file);
                    }
                }
                closedir($handle);
            }
            return $file_list;
        }

        /**
         * Return an array of user names
         * @return array
         */
        public function getUserIdNums()
        {
            $query = "
              SELECT
                id,
                username
              FROM
                {$GLOBALS['CONFIG']['db_prefix']}user
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetchAll();

            return $result;
        }

        public static function get_db_version($prefix = '')
        {
            global $pdo;
            if(empty($prefix)) {
                $prefix = !empty($_SESSION['db_prefix']) ? $_SESSION['db_prefix'] : $GLOBALS['CONFIG']['db_prefix'];
            }
            $query1 = "SHOW TABLES LIKE :table";
            $stmt = $pdo->prepare($query1);
            $stmt->execute(array(':table' => $prefix . 'odmsys'));

            if ($stmt->rowCount() > 0) {
                $query2 = "SELECT sys_value from {$prefix}odmsys WHERE sys_name='version'";
                $stmt = $pdo->prepare($query2);
                $stmt->execute();
                $result_array = $stmt->fetch();
            }

            $db_version = (!empty($result_array['sys_value']) ? $result_array['sys_value'] : 'Unknown');
            return $db_version;
        }
    }
}
