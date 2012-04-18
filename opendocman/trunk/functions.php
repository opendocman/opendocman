<?php
/*
functions.php - various utility functions
Copyright (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
Copyright (C) 2008-2011 Stephen Lawrence Jr.

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
$plugin= new Plugin();

// Set the Smarty variables
require_once('includes/smarty/Smarty.class.php');
$GLOBALS['smarty'] = new Smarty();
$GLOBALS['smarty']->template_dir = dirname(__FILE__) . '/templates/' . $GLOBALS['CONFIG']['theme'] .'/';
$GLOBALS['smarty']->compile_dir = dirname(__FILE__) . '/templates_c/';

/**** SET g_ vars from Global Config arr ***/
foreach($GLOBALS['CONFIG'] as $key => $value)
{
    $GLOBALS['smarty']->assign('g_' . $key,$value);
}

include_once('classHeaders.php');
include_once('mimetypes.php');
require_once('crumb.php');
require_once('secureurl.class.php');
include_once('secureurl.php');
include('udf_functions.php');
require_once('Category_class.php');
include_once('includes/language/' . $GLOBALS['CONFIG']['language'] . '.php');

/* Set language  vars */
foreach($GLOBALS['lang'] as $key=>$value)
{
    $GLOBALS['smarty']->assign('g_lang_' . $key, msg($key));
}

// Check if dataDir is working
if(!is_dir($GLOBALS['CONFIG']['dataDir']))
{
    echo $GLOBALS['lang']['message_datadir_problem_exists'] . ' <a href="settings.php?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
}
elseif(!is_writable($GLOBALS['CONFIG']['dataDir']))
{
    echo $GLOBALS['lang']['message_datadir_problem_writable'] . ' <a href="settings.php?submit=update"> ' . $GLOBALS['lang']['label_settings'] . '</a><br />';
}


// BEGIN FUNCTIONS
// function to format mySQL DATETIME values
function fix_date($val)
{
    //split it up into components
    if( $val != 0 )
    {
        $arr = explode(' ', $val);
        $timearr = explode(':', $arr[1]);
        $datearr = explode('-', $arr[0]);
        // create a timestamp with mktime(), format it with date()
        return date('d M Y (H:i)', mktime($timearr[0], $timearr[1], $timearr[2], $datearr[1], $datearr[2], $datearr[0]));
    }
    else
    {
        return 0;
    }
}

// Return a copy of $string where all the spaces are converted into underscores
function space_to_underscore($string)
{
    $string_len = strlen($string);
    $index = 0;
    while( $index< $string_len )
    {
        if($string[$index] == ' ')
        {
            $string[$index]= '_';
        }
        $index++;
    }
    return $string;
}
// Draw the status bar for each page
function draw_status_bar()
{
    //echo '<td bgcolor="#0000A0" align="left" valign="middle" width="110">'."\n";
    //echo '<b><font size="-2" face="Arial" color="White">'."\n";
    //echo $message;
    //echo '</font></b></td>'."\n";
    return;
}


function my_sort ($id_array, $sort_order = 'asc', $sort_by = 'id')
{
    if(!isset($id_array[0]))
    {
        return $id_array;
    }
    if (sizeof($id_array) == 0 )
    {
        return $id_array;
    }
    $lwhere_or_clause = '';
    if( $sort_by == 'id' )
    {
        $lquery = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}data ORDER BY id $sort_order";
    }
    elseif($sort_by == 'author')
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id
						FROM {$GLOBALS['CONFIG']['db_prefix']}data,{$GLOBALS['CONFIG']['db_prefix']}user 
						WHERE {$GLOBALS['CONFIG']['db_prefix']}data.owner = {$GLOBALS['CONFIG']['db_prefix']}user.id 
						ORDER BY {$GLOBALS['CONFIG']['db_prefix']}user.last_name $sort_order, {$GLOBALS['CONFIG']['db_prefix']}user.first_name $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    elseif($sort_by == 'file_name')
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data ORDER BY {$GLOBALS['CONFIG']['db_prefix']}data.realname $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    elseif($sort_by == 'department')
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}department WHERE {$GLOBALS['CONFIG']['db_prefix']}data.department = {$GLOBALS['CONFIG']['db_prefix']}department.id ORDER BY {$GLOBALS['CONFIG']['db_prefix']}department.name $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    elseif($sort_by == 'created_date' )
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data ORDER BY {$GLOBALS['CONFIG']['db_prefix']}data.created $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    elseif($sort_by == 'modified_on')
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}log, {$GLOBALS['CONFIG']['db_prefix']}data WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = {$GLOBALS['CONFIG']['db_prefix']}log.id AND {$GLOBALS['CONFIG']['db_prefix']}log.revision=\"current\" GROUP BY id ORDER BY modified_on $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    elseif($sort_by == 'description')
    {
        $lquery = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data ORDER BY {$GLOBALS['CONFIG']['db_prefix']}data.description $sort_order, {$GLOBALS['CONFIG']['db_prefix']}data.id asc";
    }
    $lresult = mysql_query($lquery) or die('Error in querying:' . $lquery . mysql_error());
    $len = mysql_num_rows($lresult);
    for($li = 0; $li<$len; $li++)
    {
        list($array[$li]) = mysql_fetch_row($lresult);
    }
    return  array_values( array_intersect($array, $id_array) );
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
function draw_header($pageTitle, $lastmessage='')
{
    $uid = (isset($_SESSION['uid']) ? $_SESSION['uid'] : '');
    
    // Is the uid set?
    if ($uid != NULL)
    {
        $current_user_obj = new User($uid, $GLOBALS['connection'], DB_NAME);
        $GLOBALS['smarty']->assign('userName', $current_user_obj->getName());
    }
    
    // Are they an Admin?
    if ($uid != NULL && $current_user_obj->isAdmin())
    {
        $GLOBALS['smarty']->assign('isadmin', 'yes');
    }
    
    if(!isset($_REQUEST['state'])) 
    {
        $_REQUEST['state']=1;
    }      
    
    $lastmessage = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

    // Set up the breadcrumbs
    $crumb = new crumb();
    $crumb->addCrumb($_REQUEST['state'], $pageTitle, $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
    $breadCrumb = $crumb->printTrail($_REQUEST['state']);
        
    $GLOBALS['smarty']->assign('breadCrumb', $breadCrumb);
    $GLOBALS['smarty']->assign('site_title', $GLOBALS['CONFIG']['title']);
    $GLOBALS['smarty']->assign('base_url', $GLOBALS['CONFIG']['base_url']);
    $GLOBALS['smarty']->assign('page_title', $pageTitle);
    $GLOBALS['smarty']->assign('lastmessage', $lastmessage);
    display_smarty_template('header.tpl');
    
    if (is_dir('install'))
    {
        echo '<span style="color: red;">' . msg('install_folder') . '</span>';
    }

}

function draw_error($message)
{
    echo '<div id="last_message">' . $message . '</div>';
}

function draw_footer()
{
    display_smarty_template('footer.tpl');
}

function email_all($mail_from, $mail_subject, $mail_body, $mail_header)
{
    $query = "SELECT Email FROM {$GLOBALS['CONFIG']['db_prefix']}user";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query . " . mysql_error());
    while( list($mail_to) = mysql_fetch_row($result) )
    {
        mail($mail_to, $mail_subject, $mail_body, $mail_header);
    }
    mysql_free_result($result);
}
function email_dept($mail_from, $dept_id, $mail_subject, $mail_body, $mail_header)
{
    $query = "SELECT Email FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE department = $dept_id";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query . " . mysql_error());
    while( list($mail_to) = mysql_fetch_row($result) )
    {
        mail($mail_to, $mail_subject, $mail_body, $mail_header);
    }
    mysql_free_result($result);
}
function email_users_obj($mail_from, $user_OBJ_array, $mail_subject, $mail_body, $mail_header)
{
    for($i = 0; $i< sizeof($user_OBJ_array); $i++)
    {
        mail($user_OBJ_array[$i]->getEmailAddress(), $mail_subject, $mail_body, $mail_header);
    }
}
function email_users_id($mail_from, $user_ID_array, $mail_subject, $mail_body, $mail_header)
{
    for($i = 0; $i<sizeof($user_ID_array); $i++)
    {
        $OBJ_array[$i] = new User($user_ID_array[$i], $GLOBALS['connection'], DB_NAME);
    }
    email_users_obj($mail_from, $OBJ_array, $mail_subject, $mail_body, $mail_header);
}

function getmicrotime()
{
    list($usec, $sec) = explode(" ",microtime());
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
function list_files($fileid_array, $userperms_obj, $dataDir, $showCheckBox = 'false', $rejectpage = 'false')
{
    //      print_r($fileid_array);exit;
    $secureurl= new phpsecureurl;
    if(sizeof($fileid_array)==0 || !isset($fileid_array[0]))
    {
        echo'<img src="images/exclamation.gif">' . msg('message_no_files_found') . "\n";
        return -1;
    }

    foreach($fileid_array as $fileid)
    {
        $file_obj = new FileData($fileid, $GLOBALS['connection'], DB_NAME);
        $userAccessLevel = $userperms_obj->getAuthority($fileid,$file_obj);
        $description = $file_obj->getDescription();
        
        if ($file_obj->getStatus() == 0 and $userAccessLevel >= $userperms_obj->VIEW_RIGHT)
        {
            $lock = false;
        }
        else
        {
            $lock = true;
        }
        if ($description == '')
        {
            $description = 'No description available';
        }
        $description = substr($description, 0, 35);
        
        // set filename for filesize() call below
        //$filename = $dataDir . $file_obj->getId() . '.dat';

        // begin displaying file list with basic information
        //$comment = $file_obj->getComment();

        $created_date = fix_date($file_obj->getCreatedDate());
        if ($file_obj->getModifiedDate())
        {
            $modified_date = fix_date($file_obj->getModifiedDate());
        }

        $full_name_array = $file_obj->getOwnerFullName();
        $owner_name = $full_name_array[1].', '.$full_name_array[0];
        //$user_obj = new User($file_obj->getOwner(), $file_obj->connection, $file_obj->database);
        $dept_name = $file_obj->getDeptName();
        $realname = $file_obj->getRealname();
        
        //$filesize = $file_obj->getFileSize();
        //Get the file size in bytes.
        $filesize = display_filesize($GLOBALS['CONFIG']['dataDir'] . $fileid. '.dat');

        if ($userAccessLevel >= $userperms_obj->READ_RIGHT)
        {
            $suffix = strtolower((substr($realname,((strrpos($realname,".")+1)))));
            if( !isset($GLOBALS['mimetypes']["$suffix"]) )
            {
                $lmimetype = $GLOBALS['mimetypes']['default'];
            }
            else
            {
                $lmimetype = $GLOBALS['mimetypes']["$suffix"];
            }

            $view_link = 'view_file.php?submit=view&id=' . urlencode($fileid).'&mimetype='.urlencode("$lmimetype");
        }
        else
        {
            $view_link = 'none';
        }

        $details_link = $secureurl->encode('details.php?id=' . $fileid . '&state=' . ($_REQUEST['state']+1));

        $read = array($userperms_obj->READ_RIGHT, 'r');
        $write = array($userperms_obj->WRITE_RIGHT, 'w');
        $admin = array($userperms_obj->ADMIN_RIGHT, 'a');
        $rights = array($read, $write, $admin);
        $index_found = -1;
        //$rights[max][0] = admin, $rights[max-1][0]=write, ..., $right[min][0]=view
        //if $userright matches with $rights[max][0], then this user has all the rights of $rights[max][0]
        //and everything below it.
        for($i = sizeof($rights)-1; $i>=0; $i--)
        {
            if($userAccessLevel==$rights[$i][0])
            {
                $index_found = $i;
                $i = 0;
            }
        }

        //Found the user right, now bold every below it.  For those that matches, make them different.
        for($i = $index_found; $i>=0; $i--)
        {
            $rights[$i][1]='<b>'. $rights[$i][1] . '</b>';
        }
        //For everything above it, blank out
        for($i = $index_found+1; $i<sizeof($rights); $i++)
        {
            $rights[$i][1] = '-';
        }
        $file_list_arr[] = array(
                'id'=>$fileid,
                'view_link'=>$view_link,
                'details_link'=>$details_link,
                'filename'=>$realname,
                'description'=>$description,
                'rights'=>$rights,
                'created_date'=>$created_date,
                'modified_date'=>$modified_date,
                'owner_name'=>$owner_name,
                'dept_name'=>$dept_name,
                'filesize'=>$filesize,
                'lock'=>$lock,
                'showCheckbox'=>$showCheckBox,
                'rejectpage'=>$rejectpage
        );
        //print_r($file_list_arr);exit;

    }

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
    ?>
<script type="text/javascript">
    var category_option = '';
    var category_item_option = '';

    function loadItem(select_box)
    {
        options_array = document.forms['browser_sort'].elements['category_item'].options;
        // Clear the list
        for(i=0; i< options_array.length; i++)
        {	options_array[i]=null;	}
        options_array.length = 0;
        switch(select_box.options[select_box.selectedIndex].value)
        {
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
                        options_array[0] = new Option('Choose ' + category_option);
                        options_array[0].id= 0;
                        options_array[0].value = 'choose_an_author';

                        for(i=0; i< info_Array.length; i++)
                        {
                            options_array[ i + 1 ]= new Option(info_Array[i][0]);
                            options_array[ i + 1 ].id= i + 1;
                            options_array[ i + 1 ].value = info_Array[i][0];
                        }
                        category_option = select_box.options[select_box.selectedIndex].value;
                    }
                    function loadOrder(select_box)
                    {
                        category_item_option = select_box.options[select_box.selectedIndex].value;
                        if(category_item_option == 'choose_an_author')
                            exit();
                        order_array = new Array();
                        order_array[0] = new Array('Ascending', 0, 'asc');
                        order_array[1] = new Array('Descending', 1, 'desc');
                        options_array = document.forms['browser_sort'].elements['category_item_order'].options;

                        options_array[0] = new Option('Choose an Order');
                        options_array[0].id= 0;
                        options_array[0].value = 'choose_an_order';
                        for(i=0; i< order_array.length; i++)
                        {
                            options_array[i+1]= new Option(order_array[i][0]);
                            options_array[i+1].id= i + 1;
                            options_array[i+1].value = order_array[i][2];
                        }
                    }

                    function load(select_box)
                    {
                        window.location = "search.php?submit=submit&sort_by=id&where=" + category_option + "_only&sort_order=" + select_box.options[select_box.selectedIndex].value + "&keyword=" + escape(category_item_option) + "&exact_phrase=on";
                    }
        <?php
        ///////////////////////////////FOR AUTHOR///////////////////////////////////////////
        $query = "SELECT last_name, first_name, id FROM {$GLOBALS['CONFIG']['db_prefix']}user ORDER BY last_name ASC";
        $result = mysql_query($query, $GLOBALS['connection']) or die('Error in query'. mysql_error());
        $count = mysql_num_rows($result);
        $index = 0;
        echo("author_array = new Array();\n");
        while($index < $count)
        {
            list($last_name, $first_name, $id) = mysql_fetch_row($result);
            echo("\tauthor_array[$index] = new Array(\"$last_name $first_name\", $id);\n");
            $index++;
        }
        ///////////////////////////////FOR DEPARTMENT//////////////////////////
        $query = "SELECT name, id FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name ASC";
        $result = mysql_query($query, $GLOBALS['connection']) or die('Error in query'. mysql_error());
        $count = mysql_num_rows($result);
        $index = 0;
        echo("department_array = new Array();\n");
        while($index < $count)
        {
            list($dept, $id) = mysql_fetch_row($result);
            echo("\tdepartment_array[$index] = new Array(\"$dept\", $id);\n");
            $index++;
        }
        ///////////////////////////////FOR FILE CATEGORY////////////////////////////////////////
        $query = "SELECT name, id FROM {$GLOBALS['CONFIG']['db_prefix']}category ORDER BY name ASC";
        $result = mysql_query($query, $GLOBALS['connection']) or die('Error in query'. mysql_error());
        $count = mysql_num_rows($result);
        $index = 0;
        echo("category_array = new Array();\n");
        while($index < $count)
        {
            list($category, $id) = mysql_fetch_row($result);
            echo("\tcategory_array[$index] = new Array(\"$category\", $id);\n");
            $index++;
        }
        udf_functions_java_array();
        ///////////////////////////////////////////////////////////////////////
        echo '</script>'."\n";
        ?>
                        <form name="browser_sort">
			<table name="browser" border="0" cellspacing="1">
			<tr><td><?php echo msg('label_browse_by');?></td>
				<td NOWRAP ROWSPAN="0">
					<select name='category' onChange='loadItem(this)' width='0' size='1'>
                                                        <option id='0' selected><?php echo msg('label_select_one');?></option>
                                                        <option id='1' value='author'><?php echo msg('author');?></option>
                                                        <option id='2' value='department'><?php echo msg('label_department');?></option>
                                                        <option id='3' value='category'><?php echo msg('label_file_category');?></option>
        <?php
        udf_functions_java_options(4);
        ?>
                        </select>
                        </td>
				<td>
					<select name='category_item' onChange='loadOrder(this)'>
                                                        <option id='0' selected><?php echo msg('label_empty');?></option>
                                                        </select>
                                                        </td>
				<td>
					<select name='category_item_order' onChange='load(this)'>
                                                        <option id='0' selected><?php echo msg('label_empty');?></option>
                                                        </select>
                                                        </td>
                                                        </tr>
                                                        </table>
                                                        </form>
    <?php
}

/////////////////////////////////////////////////Debuging function/////////////////////////////////
function display_array($array)
{
    for($i=0; $i<sizeof($array); $i++)
    {
        echo($i.":".$array[$i]."<br>");
    }
}
function display_array2D($array)
{
    for($i=0; $i<sizeof($array); $i++)
    {
        for($j=0; $j<sizeof($array[$i]); $j++)
        {
            echo($i.":"."$j".":".$array[$i][$j]."<br>");
        }
    }
}
function makeRandomPassword()
{
    $pass='';
    $salt = 'abchefghjkmnpqrstuvw3456789';
    srand((double)microtime()*1000000);
    $i = 0;
    while ($i <= 7)
    {
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
    $userperm_obj = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    if(!$userperm_obj->user_obj->isAdmin() && $userperm_obj->getAuthority($file_id, $obj) < $permittable_right)
    {
        echo msg('error').': '.msg('message_unable_to_find_file') . "\n";
        echo '       ' . msg('message_please_email') . ' <a href="mailto:' . $GLOBALS['CONFIG']['site_mail'] . '">' . msg('area_admin') . '</a>';
        exit();
    }
}
function fmove($source_file, $destination_file)
{
    //read and close
    $lfhandler = fopen ($source_file, "r");
    $lfcontent = fread($lfhandler, filesize ($source_file));
    fclose ($lfhandler);
    //write and close
    $lfhandler = fopen ($destination_file, "w");
    fwrite($lfhandler, $lfcontent);
    fclose ($lfhandler);
    //delete source file
    unlink($source_file);
}
/* return a 2D array of users.
    array[0][0] = id
    array[0][1] = "LastName, FirstName"
    array[0][2] = "username"
*/
function getAllUsers()
{
    $lquery = "SELECT id, last_name, first_name, username FROM {$GLOBALS['CONFIG']['db_prefix']}user";
    $lresult = mysql_query($lquery) or die(msg('error'). ':'. $lquery . mysql_error());
    $llen = mysql_num_rows($lresult);
    $return_array = array();
    for($li = 0;$li<$llen; $li++)
    {
        list($lid, $llast_name, $lfirst_name, $lusername) = mysql_fetch_row($lresult);
        $return_array[$li] = array($lid, "$llast_name, $lfirst_name", $lusername);
    }
    return $return_array;
}
function display_filesize($file)
{
    // Does the file exist?
    if(is_file($file))
    {

        //Setup some common file size measurements.
        $kb=1024;
        $mb=1048576;
        $gb=1073741824;
        $tb=1099511627776;

        //Get the file size in bytes.
        $size = filesize($file);

        //Format file size

        if($size < $kb)
        {
            return $size." B";
        }
        elseif($size < $mb)
        {
            return round($size/$kb,2)." KB";
        }
        elseif($size < $gb)
        {
            return round($size/$mb,2)." MB";
        }
        elseif($size < $tb)
        {
            return round($size/$gb,2)." GB";
        }
        else
        {
            return round($size/$tb,2)." TB";
        }
    }
    else
    {
        return "X";
    }
}
function valid_username($username)
{
    if(preg_match('/^\w+$/',$username))
        return true;
    else
        return false;
}


function cleanInput($input)
{

    $search = array(
            '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
            '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
            '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
            '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );
    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitizeme($input)
{
    if (is_array($input))
    {
        foreach($input as $var=>$val)
        {
            $output[$var] = sanitizeme($val);
        }
    }
    else
    {
        if (get_magic_quotes_gpc())
        {
            $input = stripslashes($input);
        }
        //echo "Raw Input:" . $input . "<br />";
        $input  = cleanInput($input);
        //echo "Clean Input:" . $input . "<br />";
        $output = mysql_real_escape_string($input);
        //echo "mysql_escape output" . $output . "<br />";

    }
    if(isset($output) && $output != '')
    {
        return $output;
    }
    else
    {
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
        if (isset($GLOBALS['lang'][$s]))
        {
            return $GLOBALS['lang'][$s];
        }
        else
        {
            //error_log("l10n error:LANG:" .
            //    $GLOBALS['CONFIG']['language']. ",message:'$s'");

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
    if(file_exists(ABSPATH . '/templates/' . $GLOBALS['CONFIG']['theme'] . '/' . $template_file))
    {
        $GLOBALS['smarty']->display($template_file);
    }
    else
    {
        $GLOBALS['smarty']->display(ABSPATH . '/templates/common/' . $template_file);
    }
}


    /*
     * callPluginMethod
     * @param string $method The name of the plugin method being envoked.
     * @param string $args Any arguments that should be passed to the plugin method
     * @return null
     */
    function callPluginMethod($method,$args='')
    {
        foreach ($GLOBALS['plugin']->pluginslist as $value)
        {
            if (!valid_username($value))
            {
                echo 'Sorry, your plugin ' . $value . ' is not setup properly';
            }
            $plugin_obj = new $value;
            $plugin_obj->$method($args);
        }
    }

    function debug_query($file, $line, $query)
    {
        if($GLOBALS['CONFIG']['debug'] == 'True')
        {
            $GLOBALS['debug_text'] .= $file . ': Line #' . $line . ": ". $query . '<br />';
        }
    }