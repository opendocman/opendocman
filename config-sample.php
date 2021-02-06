<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
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

// OpenDocMan sample config file

// Eliminate multiple inclusions
if (!defined('config')) {
    define('config', 'true', false);

    // config.php - you should not need to change these
    /** The name of the database for [OpenDocMan */
    define('APP_DB_NAME', isset($_ENV['APP_DB_NAME']) ? $_ENV['APP_DB_NAME'] : 'database_name_here');

    /** MySQL database username */
    define('APP_DB_USER', isset($_ENV['APP_DB_USER']) ? $_ENV['APP_DB_USER'] : 'username_here');

    /** MySQL database password */
    define('APP_DB_PASS', isset($_ENV['APP_DB_PASS'])? $_ENV['APP_DB_PASS'] : 'password_here');

    /** MySQL hostname */
    /* The MySQL server. It can also include a port number. e.g. "hostname;port=3306" or a path to a
     * local socket e.g. ":/path/to/socket" for the localhost.  */
    define('APP_DB_HOST', isset($_ENV['APP_DB_HOST']) ? $_ENV['APP_DB_HOST'] : 'localhost');

    /**
     * Prefix to append to each table name in the database (ex. odm_ would make the tables
     * named "odm_users", "odm_data" etc. Leave this set to the default if you want to keep
     * it the way it was. If you do change this to a different value, make sure it is either
     * a clean-install, or you manually go through and re-name the database tables to match.
     * @DEFAULT 'odm_'
     * @ARG String
     */
    $GLOBALS['CONFIG']['db_prefix'] = isset($_ENV['DB_PREFIX']) ? $_ENV['DB_PREFIX'] : 'odm_';

    /*** DO NOT EDIT BELOW THIS LINE ***/



    /** Absolute path to the OpenDocMan directory. */
    if (!defined('ABSPATH')) {
        if(isset($_ENV['IS_DOCKER'])) {
            define('ABSPATH', dirname(__FILE__) . '/../');
        } else {
            define('ABSPATH', dirname(__FILE__) . '/');
        }
    }
}
