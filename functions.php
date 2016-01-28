<?php
use Aura\Html\Escaper as e;

/*
functions.php - various utility functions
Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
Copyright (C) 2008-2013 Stephen Lawrence Jr.

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

include_once('version.php');

include_once('Plugin_class.php');
$plugin = new Plugin();

// Set the Smarty variables
require_once('includes/smarty/Smarty.class.php');
$GLOBALS['smarty'] = new Smarty();
$GLOBALS['smarty']->template_dir = dirname(__FILE__) . '/templates/' . $GLOBALS['CONFIG']['theme'] . '/';
$GLOBALS['smarty']->compile_dir = dirname(__FILE__) . '/templates_c/';

/**** SET g_ vars from Global Config arr ***/
foreach ($GLOBALS['CONFIG'] as $key => $value) {
    $GLOBALS['smarty']->assign('g_' . $key, $value);
}

include_once __DIR__ .'/vendor/owasp/csrf-protector-php/libs/csrf/csrfprotector.php';
csrfProtector::init();

include_once('classHeaders.php');
include_once('mimetypes.php');
require_once('crumb.php');
include('udf_functions.php');
require_once('Category_class.php');
include_once('includes/language/' . $GLOBALS['CONFIG']['language'] . '.php');
require_once("File_class.php");

/* Set language  vars */
foreach ($GLOBALS['lang'] as $key => $value) {
    $GLOBALS['smarty']->assign('g_lang_' . $key, msg($key));
}

// Check if dataDir is working
if (!is_dir($GLOBALS['CONFIG']['dataDir'])) {
    echo $GLOBALS['lang']['message_datadir_problem_exists'] . ' <a href="settings.php?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
} elseif (!is_writable($GLOBALS['CONFIG']['dataDir'])) {
    echo $GLOBALS['lang']['message_datadir_problem_writable'] . ' <a href="settings.php?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
}


// BEGIN FUNCTIONS
// function to format mySQL DATETIME values
function fix_date($val)
{
    //split it up into components
    if ($val != 0) {
        $arr = explode(' ', $val);
        $timearr = explode(':', $arr[1]);
        $datearr = explode('-', $arr[0]);
        // create a timestamp with mktime(), format it with date()
        return date('d M Y (H:i)', mktime($timearr[0], $timearr[1], $timearr[2], $datearr[1], $datearr[2], $datearr[0]));
    } else {
        return 0;
    }
}

// Return a copy of $string where all the spaces are converted into underscores
function space_to_underscore($string)
{
    $string_len = strlen($string);
    $index = 0;
    while ($index < $string_len) {
        if ($string[$index] == ' ') {
            $string[$index] = '_';
        }
        $index++;
    }
    return $string;
}

// Draw the status bar for each page
function draw_status_bar()
{
    return;
}

// This function draws the menu screen
function draw_menu()
{
    return;
}

/*
 * draw_header - Draw the header area from the template file
 * @param string $pageTitle The title from the settings.
 * @param string $lastmessage Any error or feedback message to be sent to screen
 */
function draw_header($pageTitle, $lastmessage = '')
{
    global $pdo;

    $uid = (isset($_SESSION['uid']) ? $_SESSION['uid'] : '');

    // Is the uid set?
    if ($uid != null) {
        $current_user_obj = new User($uid, $pdo);
        $GLOBALS['smarty']->assign('userName', $current_user_obj->getName());
        $GLOBALS['smarty']->assign('can_add', $current_user_obj->can_add);
        $GLOBALS['smarty']->assign('can_checkin', $current_user_obj->can_checkin);
    }

    // Are they an Admin?
    if ($uid != null && $current_user_obj->isAdmin()) {
        $GLOBALS['smarty']->assign('isadmin', 'yes');
    }

    if (!isset($_REQUEST['state'])) {
        $_REQUEST['state'] = 1;
    }

    $lastmessage = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : $lastmessage);

    // Set up the breadcrumbs
    $crumb = new crumb();
    $crumb->addCrumb(e::h($_REQUEST['state']), e::h($pageTitle), e::h($_SERVER['PHP_SELF']) . '?' . e::h($_SERVER['QUERY_STRING']));
    $breadCrumb = $crumb->printTrail(e::h($_REQUEST['state']));

    $GLOBALS['smarty']->assign('breadCrumb', $breadCrumb);
    $GLOBALS['smarty']->assign('site_title', $GLOBALS['CONFIG']['title']);
    $GLOBALS['smarty']->assign('base_url', $GLOBALS['CONFIG']['base_url']);
    $GLOBALS['smarty']->assign('page_title', $pageTitle);
    $GLOBALS['smarty']->assign('lastmessage', urldecode($lastmessage));
    display_smarty_template('header.tpl');

}

function draw_error($message)
{
    echo '<div id="last_message">' . e::h(urldecode($message)) . '</div>';
}

function draw_footer()
{
    display_smarty_template('footer.tpl');
}

/**
 * @param string $mail_subject
 * @param string $mail_body
 * @param string $mail_header
 */
function email_all($mail_subject, $mail_body, $mail_header)
{
    global $pdo;

    $query = "
      SELECT
        Email
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}user
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchColumn();

    foreach ($result as $row) {
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($row['Email'], $mail_subject, $mail_body, $mail_header);
        }
    }
}

/**
 * @param int $dept_id
 * @param string $mail_subject
 * @param string $mail_body
 * @param string $mail_header
 */
function email_dept($dept_id, $mail_subject, $mail_body, $mail_header)
{
    global $pdo;

    $query = "
      SELECT
        Email
      FROM
        {$GLOBALS['CONFIG']['db_prefix']}user
      WHERE
        department = :dept_id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(
        ':dept_id' => $dept_id
    ));
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($row['Email'], $mail_subject, $mail_body, $mail_header);
        }
    }
}

/**
 * @param obj $user_OBJ_array
 * @param string $mail_subject
 * @param string $mail_body
 * @param string $mail_header
 */
function email_users_obj($user_OBJ_array, $mail_subject, $mail_body, $mail_header)
{
    for ($i = 0; $i < sizeof($user_OBJ_array); $i++) {
        if ($GLOBALS['CONFIG']['demo'] == 'False') {
            mail($user_OBJ_array[$i]->getEmailAddress(), $mail_subject, $mail_body, $mail_header);
        }
    }
}

/**
 * @param array $user_ID_array
 * @param string $mail_subject
 * @param string $mail_body
 * @param string $mail_header
 */
function email_users_id($user_ID_array, $mail_subject, $mail_body, $mail_header)
{
    global $pdo;

    for ($i = 0; $i < sizeof($user_ID_array); $i++) {
        if (($user_ID_array[$i] > 0)) {
            $OBJ_array[$i] = new User($user_ID_array[$i], $pdo);
        }
    }

    if (count($OBJ_array) > 0) {
        email_users_obj($OBJ_array, $mail_subject, $mail_body, $mail_header);
    }
}

function getmicrotime()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * list_files - Display a list of files
 * @return NULL
 * @param array $fileid_array
 * @param object $userperms_obj
 * @param string $dataDir
 * @param boolean $showCheckBox
 * @param boolean $rejectpage
 */
function list_files($fileid_array, $userperms_obj, $dataDir, $showCheckBox = false, $rejectpage = false)
{
    global $pdo;

    if (sizeof($fileid_array) == 0 || !isset($fileid_array[0])) {
        echo '<img src="images/exclamation.gif">' . msg('message_no_files_found') . PHP_EOL;
        return -1;
    }

    foreach ($fileid_array as $fileid) {
        $file_obj = new FileData($fileid, $pdo);
        $userAccessLevel = $userperms_obj->getAuthority($fileid, $file_obj);
        $description = $file_obj->getDescription();

        if ($file_obj->getStatus() == 0 and $userAccessLevel >= $userperms_obj->VIEW_RIGHT) {
            $lock = false;
        } else {
            $lock = true;
        }
        if ($description == '') {
            $description = msg('message_no_description_available');
        }

        $created_date = fix_date($file_obj->getCreatedDate());
        if ($file_obj->getModifiedDate()) {
            $modified_date = fix_date($file_obj->getModifiedDate());
        } else {
            $modified_date = $created_date;
        }

        $full_name_array = $file_obj->getOwnerFullName();
        $owner_name = $full_name_array[1] . ', ' . $full_name_array[0];
        $dept_name = $file_obj->getDeptName();
        $realname = $file_obj->getRealname();

        //Get the file size in bytes.
        $filesize = display_filesize($dataDir . $fileid . '.dat');

        if ($userAccessLevel >= $userperms_obj->READ_RIGHT) {
            $suffix = strtolower((substr($realname, ((strrpos($realname, ".") + 1)))));
            $mimetype = File::mime_by_ext($suffix);
            $view_link = 'view_file.php?submit=view&id=' . urlencode(e::h($fileid)) . '&mimetype=' . urlencode("$mimetype");
        } else {
            $view_link = 'none';
        }

        $details_link = 'details.php?id=' . e::h($fileid) . '&state=' . (e::h($_REQUEST['state'] + 1));

        $read = array($userperms_obj->READ_RIGHT, 'r');
        $write = array($userperms_obj->WRITE_RIGHT, 'w');
        $admin = array($userperms_obj->ADMIN_RIGHT, 'a');
        $rights = array($read, $write, $admin);
        $index_found = -1;
        //$rights[max][0] = admin, $rights[max-1][0]=write, ..., $right[min][0]=view
        //if $userright matches with $rights[max][0], then this user has all the rights of $rights[max][0]
        //and everything below it.
        for ($i = sizeof($rights) - 1; $i >= 0; $i--) {
            if ($userAccessLevel == $rights[$i][0]) {
                $index_found = $i;
                $i = 0;
            }
        }

        //Found the user right, now bold every below it.  For those that matches, make them different.
        
        //For everything above it, blank out
        for ($i = $index_found + 1; $i < sizeof($rights); $i++) {
            $rights[$i][1] = '-';
        }
        $file_list_arr[] = array(
            'id' => $fileid,
            'view_link' => $view_link,
            'details_link' => $details_link,
            'filename' => $realname,
            'description' => $description,
            'rights' => $rights,
            'created_date' => $created_date,
            'modified_date' => $modified_date,
            'owner_name' => $owner_name,
            'dept_name' => $dept_name,
            'filesize' => $filesize,
            'lock' => $lock,
            'showCheckbox' => $showCheckBox,
            'rejectpage' => $rejectpage
        );
        //print_r($file_list_arr);exit;
    }

    $limit_reached = false;
    if (count($file_list_arr) >= $GLOBALS['CONFIG']['max_query']) {
        $limit_reached = true;
    }

    $GLOBALS['smarty']->assign('limit_reached', $limit_reached);
    $GLOBALS['smarty']->assign('showCheckBox', $showCheckBox);
    //print_r($file_list_arr);exit;
    $GLOBALS['smarty']->assign('file_list_arr', $file_list_arr);
    //print_r($GLOBALS['smarty']);

    // Call the plugin API
    callPluginMethod('onBeforeListFiles', $file_list_arr);

    display_smarty_template('out.tpl');

    callPluginMethod('onAfterListFiles');
}

function sort_browser()
{
    global $pdo;

    ?>
    <script type="text/javascript">
        var category_option = '';
        var category_item_option = '';

        function loadItem(select_box) {
            options_array = document.forms['browser_sort'].elements['category_item'].options;
            // Clear the list
            for (i = 0; i < options_array.length; i++) {
                options_array[i] = null;
            }
            options_array.length = 0;
            switch (select_box.options[select_box.selectedIndex].value) {
                case 'author':
                    info_Array = author_array;
                    break;
                case 'department':
                    info_Array = department_array;
                    break;
                case 'category':
                    info_Array = category_array;
                    break;
                <?php
                udf_functions_java_menu();
            ?>
                default :
                    order_array = document.forms['browser_sort'].elements['category_item_order'].options;
                    info_Array = new Array();
                    info_Array[0] = new Array('Empty', 0);
                    break;
            }
            category_option = select_box.options[select_box.selectedIndex].value;
            switch (category_option) {
                case 'author':
                <?php
                echo("\tcategory_option_msg = '".msg('category_option_author')."';".PHP_EOL);
                ?>
                    break;
                case 'department':
                <?php
                echo("\tcategory_option_msg = '".msg('category_option_department')."';".PHP_EOL);
                ?>
                    break;
                case 'category':
                <?php
                echo("\tcategory_option_msg = '".msg('category_option_category')."';".PHP_EOL);
                ?>
                    break;
                default :
                <?php
                echo("\tcategory_option_msg = '".msg('label_empty')."';".PHP_EOL);
                ?>
                    break;
            }
            <?php
            echo("\toptions_array[0] = new Option('".msg('outpage_choose')." ' + category_option_msg);".PHP_EOL);
            ?>
            options_array[0].id = 0;
            options_array[0].value = 'choose_an_author';

            for (i = 0; i < info_Array.length; i++) {
                options_array[i + 1] = new Option(info_Array[i][0]);
                options_array[i + 1].id = i + 1;
                options_array[i + 1].value = info_Array[i][0];
            }
            category_option = select_box.options[select_box.selectedIndex].value;
        }
        function loadOrder(select_box) {
            category_item_option = select_box.options[select_box.selectedIndex].value;
            if (category_item_option == 'choose_an_author')
                exit();
            order_array = new Array();
            <?php
            echo("\torder_array[0] = new Array(\"".msg('outpage_ascending')."\", 0, \"asc\");".PHP_EOL);
            echo("\torder_array[1] = new Array(\"".msg('outpage_descending')."\", 0, \"desc\");".PHP_EOL);
            echo("\toptions_array = document.forms['browser_sort'].elements['category_item_order'].options;".PHP_EOL);

            echo("\toptions_array[0] = new Option('".msg('outpage_choose_an_order')."');".PHP_EOL);
                        ?>
            options_array[0].id = 0;
            options_array[0].value = 'choose_an_order';
            for (i = 0; i < order_array.length; i++) {
                options_array[i + 1] = new Option(order_array[i][0]);
                options_array[i + 1].id = i + 1;
                options_array[i + 1].value = order_array[i][2];
            }
        }

        function load(select_box) {
            window.location = "search.php?submit=submit&sort_by=id&where=" + category_option + "&sort_order=" + select_box.options[select_box.selectedIndex].value + "&keyword=" + escape(category_item_option) + "&exact_phrase=on";
        }
        <?php
        ///////////////////////////////FOR AUTHOR///////////////////////////////////////////
        $query = "
          SELECT
            last_name,
            first_name,
            id
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}user
          ORDER BY
            last_name ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $index = 0;
        echo("author_array = new Array();".PHP_EOL);
        foreach($result as $row) {
            $last_name = e::h($row['last_name']);
            $first_name = e::h($row['first_name']);
            $id = e::h($row['id']);
            echo("\tauthor_array[$index] = new Array(\"$last_name $first_name\", $id);".PHP_EOL);
            $index++;
        }

        ///////////////////////////////FOR DEPARTMENT//////////////////////////
        $query = "
          SELECT
            name,
            id
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}department
          ORDER BY
            name ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $index = 0;
        echo("department_array = new Array();".PHP_EOL);
        foreach($result as $row) {
            $dept = e::h($row['name']);
            $id = e::h($row['id']);
            echo("\tdepartment_array[$index] = new Array(\"$dept\", $id);".PHP_EOL);
            $index++;
        }

        ///////////////////////////////FOR FILE CATEGORY////////////////////////////////////////
        $query = "
          SELECT
            name,
            id
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}category
          ORDER BY
            name ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $index = 0;
        echo("category_array = new Array();".PHP_EOL);
        foreach($result as $row) {
            $category = e::h($row['name']);
            $id = e::h($row['id']);
            echo("\tcategory_array[$index] = new Array(\"$category\", $id);".PHP_EOL);
            $index++;
        }
        udf_functions_java_array();
        ///////////////////////////////////////////////////////////////////////
        echo '</script>'.PHP_EOL;
?>
        <form name = "browser_sort">
            <table name = "browser" border = "0" cellspacing = "1">
            <tr>
              <td><?php echo msg('label_browse_by');?></td>
              <td nowrap rowspan="0">
                <select name="category" onChange="loadItem(this)" width="0" size="1">
                    <option id="0" selected ><?php echo msg('label_select_one');?> </option>
                    <option id="1" value="author"><?php echo msg('author');?> </option>
                    <option id="2" value="department"><?php echo msg('label_department');?></option>
                    <option id="3" value="category"><?php echo msg('label_file_category');?></option>
            <?php
            udf_functions_java_options(4);
        ?>
                </select>
            </td>
            <td>
                <select name="category_item" onChange="loadOrder(this)">
                    <option id="0" selected ><?php echo msg('label_empty');?></option>
                </select>
            </td>
            <td>
                <select name="category_item_order" onChange="load(this)">
                    <option id="0" selected ><?php echo msg('label_empty');?></option>
                </select>
            </td>
        </tr>
    </table>
</form >
    <?php

}

/////////////////////////////////////////////////Debuging function/////////////////////////////////
function display_array($array)
{
    for ($i = 0; $i < sizeof($array); $i++) {
        echo($i . ":" . $array[$i] . "<br>");
    }
}

function display_array2D($array)
{
    for ($i = 0; $i < sizeof($array); $i++) {
        for ($j = 0; $j < sizeof($array[$i]); $j++) {
            echo($i . ":" . "$j" . ":" . $array[$i][$j] . "<br>");
        }
    }
}

function makeRandomPassword()
{
    $pass = '';
    $salt = 'abchefghjkmnpqrstuvw3456789';
    srand((double)microtime() * 1000000);
    $i = 0;
    while ($i <= 7) {
        $num = rand() % 33;
        $tmp = substr($salt, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }
    return $pass;
}

/*
 * @param $file_id int
 * @param $permittable_right int the right value requested
 * @param $obj object an object reference that has access to Database class static vars (VIEW_RIGHT, etc)
 */
function checkUserPermission($file_id, $permittable_right, $obj)
{
    global $pdo;

    $userperm_obj = new UserPermission($_SESSION['uid'], $pdo);
    if (!$userperm_obj->user_obj->isAdmin() && $userperm_obj->getAuthority($file_id, $obj) < $permittable_right) {
        echo msg('error') . ': ' . msg('message_unable_to_find_file') . PHP_EOL;
        echo '       ' . msg('message_please_email') . ' <a href="mailto:' . $GLOBALS['CONFIG']['site_mail'] . '">' . msg('area_admin') . '</a>';
        exit();
    }
}

function fmove($source_file, $destination_file)
{
    //read and close
    $fhandler = fopen($source_file, "r");
    $fcontent = fread($fhandler, filesize($source_file));
    fclose($fhandler);
    //write and close
    $fhandler = fopen($destination_file, "w");
    fwrite($fhandler, $fcontent);
    fclose($fhandler);
    //delete source file
    unlink($source_file);
}

function display_filesize($file)
{
    // Does the file exist?
    if (is_file($file)) {

        //Setup some common file size measurements.
        $kb = 1024;
        $mb = 1048576;
        $gb = 1073741824;
        $tb = 1099511627776;

        //Get the file size in bytes.
        $size = filesize($file);

        //Format file size

        if ($size < $kb) {
            return $size . " B";
        } elseif ($size < $mb) {
            return round($size / $kb, 2) . " KB";
        } elseif ($size < $gb) {
            return round($size / $mb, 2) . " MB";
        } elseif ($size < $tb) {
            return round($size / $gb, 2) . " GB";
        } else {
            return round($size / $tb, 2) . " TB";
        }
    } else {
        return "X";
    }
}

function valid_username($username)
{
    if (preg_match('/^\w+$/', $username)) {
        return true;
    } else {
        return false;
    }
}


function cleanInput($input)
{
    $output = xss_clean($input);
    /*
    $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );

    $output = preg_replace($search, '', $input);
*/
    return $output;
}

function sanitizeme($input)
{
    if (is_array($input)) {
        foreach ($input as $var => $val) {
            $output[$var] = sanitizeme($val);
        }
    } else {
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        //echo "Raw Input:" . $input . "<br />";
        $input = cleanInput($input);
        //echo "Clean Input:" . $input . "<br />";
        $output = $input;
        //echo "mysql_escape output" . $output . "<br />";
    }
    if (isset($output) && $output != '') {
        return $output;
    } else {
        return false;
    }
}

/**
 * Translate a string using the global lang set.
 * @param string $s
 * @return string
 */
function msg($s)
{
    if (isset($GLOBALS['lang'][$s])) {
        return e::h($GLOBALS['lang'][$s]);
    } else {
        return $s;
    }
}

/*
 * This function will check for the existence of a template file
 * in the current template folder and if not there will search for it
 * in the templates/common folder. This is a form of over-ride for customizations
 * @param string $template_file The name of the template file ending in .tpl
*/
function display_smarty_template($template_file)
{
    /* @var $template_file string */
    if (file_exists(ABSPATH . '/templates/' . $GLOBALS['CONFIG']['theme'] . '/' . $template_file)) {
        $GLOBALS['smarty']->display($template_file);
    } else {
        $GLOBALS['smarty']->display(ABSPATH . '/templates/common/' . $template_file);
    }
}

/*
 * callPluginMethod
 * @param string $method The name of the plugin method being envoked.
 * @param string $args Any arguments that should be passed to the plugin method
 * @return null
 */

function callPluginMethod($method, $args = '')
{
    foreach ($GLOBALS['plugin']->pluginslist as $value) {
        if (!valid_username($value)) {
            echo 'Sorry, your plugin ' . e::h($value) . ' is not setup properly';
        }
        $plugin_obj = new $value;
        $plugin_obj->$method($args);
    }
}

function debug_query($file, $line, $query)
{
    if ($GLOBALS['CONFIG']['debug'] == 'True') {
        $GLOBALS['debug_text'] .= $file . ': Line #' . $line . ": " . $query . '<br />';
    }
}

function xss_clean($str)
{
    // http://svn.bitflux.ch/repos/public/popoon/trunk/classes/externalinput.php
    // +----------------------------------------------------------------------+
    // | Copyright (c) 2001-2006 Bitflux GmbH                                 |
    // +----------------------------------------------------------------------+
    // | Licensed under the Apache License, Version 2.0 (the "License");      |
    // | you may not use this file except in compliance with the License.     |
    // | You may obtain a copy of the License at                              |
    // | http://www.apache.org/licenses/LICENSE-2.0                           |
    // | Unless required by applicable law or agreed to in writing, software  |
    // | distributed under the License is distributed on an "AS IS" BASIS,    |
    // | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
    // | implied. See the License for the specific language governing         |
    // | permissions and limitations under the License.                       |
    // +----------------------------------------------------------------------+
    // | Author: Christian Stocker <chregu@bitflux.ch>                      |
    // +----------------------------------------------------------------------+
    //
    // Kohana Modifications:
    // * Changed double quotes to single quotes, changed indenting and spacing
    // * Removed magic_quotes stuff
    // * Increased regex readability:
    //   * Used delimeters that aren't found in the pattern
    //   * Removed all unneeded escapes
    //   * Deleted U modifiers and swapped greediness where needed
    // * Increased regex speed:
    //   * Made capturing parentheses non-capturing where possible
    //   * Removed parentheses where possible
    //   * Split up alternation alternatives
    //   * Made some quantifiers possessive
    // * Handle arrays recursively

    if (is_array($str) or is_object($str)) {
        foreach ($str as $k => $s) {
            $str[$k] = xss_clean($s);
        }

        return $str;
    }

    // Remove all NULL bytes
    $str = str_replace("\0", '', $str);

    // Fix &entity\n;
    $str = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $str);
    $str = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $str);
    $str = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $str);
    $str = html_entity_decode($str, ENT_COMPAT);

    // Remove any attribute starting with "on" or xmlns
    $str = preg_replace('#(?:on[a-z]+|xmlns)\s*=\s*[\'"\x00-\x20]?[^\'>"]*[\'"\x00-\x20]?\s?#iu', '', $str);

    // Remove javascript: and vbscript: protocols
    $str = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $str);
    $str = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $str);
    $str = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $str);

    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
    $str = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#is', '$1>', $str);
    $str = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#is', '$1>', $str);
    $str = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#ius', '$1>', $str);

    // Remove namespaced elements (we do not need them)
    $str = preg_replace('#</*\w+:\w[^>]*+>#i', '', $str);

    // Remove any attempts to pass-in a script tag obfuscated by spaces
    $str = preg_replace('#<\s?/?\s*[Ss]\s*[cC]\s*[rR]\s*[iI]\s*[pP]\s*[tT]#', '', $str);

    // Removed ;base64 data usage
    $str = preg_replace('#data:*[^;]+;base64,#i', 'nodatabase64', $str);

    do {
        // Remove really unwanted tags
        $old = $str;
        $str = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);
    } while ($old !== $str);

    return $str;
}

/**
 * Custom redirection handler
 * @param string $url the internal page to redirect them to
 */
function redirect_visitor($url = '')
{
    if ($url == '') {
        header('Location:index.php?redirection=' . urlencode(e::h($_SERVER['PHP_SELF']) . '?' . e::h($_SERVER['QUERY_STRING'])));
        exit;
    } else {
        // Lets make sure its not an outside URL
        if (!preg_match('#^(http|https|ftp)://#', $url)) {
            header('Location:' . htmlentities($url, ENT_QUOTES));
            exit;
        } else {
            header('Location:index.php');
            exit;
        }
    }
}
