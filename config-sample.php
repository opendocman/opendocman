<?php
/*
config.php - OpenDocMan database config file
Copyright (C) 2011 Stephen Lawrence Jr.

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

// Eliminate multiple inclusion of config.php
if (!defined('config')) {
    define('config', 'true', false);

    // config.php - useful variables/functions
    $db_name = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'opendocman';
    $db_user = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'opendocman';
    $db_pass = isset($_ENV['DB_PASS'])? $_ENV['DB_PASS'] : 'opendocman';
    $db_host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost;port=3306';
    $db_prefix = isset($_ENV['DB_PREFIX']) ? $_ENV['DB_PREFIX'] : 'odm_';

    // ** MySQL settings - You can get this info from your web host ** //
    /** The name of the database for [OpenDocMan */
    define('DB_NAME', $db_name);

    /** MySQL database username */
    define('DB_USER', $db_user);

    /** MySQL database password */
    define('DB_PASS', $db_pass);

    /** MySQL hostname */
    /* The MySQL server. It can also include a port number. e.g. "hostname;port=3306" or a path to a
     * local socket e.g. ":/path/to/socket" for the localhost.  */
    define('DB_HOST', $db_host);

    /**
     * Prefix to append to each table name in the database (ex. odm_ would make the tables
     * named "odm_users", "odm_data" etc. Leave this set to the default if you want to keep
     * it the way it was. If you do change this to a different value, make sure it is either
     * a clean-install, or you manually go through and re-name the database tables to match.
     * @DEFAULT 'odm_'
     * @ARG String
     */
    $GLOBALS['CONFIG']['db_prefix'] = $db_prefix;

    /*** DO NOT EDIT BELOW THIS LINE ***/



    /** Absolute path to the OpenDocMan directory. */
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/');
    }
}
