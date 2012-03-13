<?php
/*
search.php - main search logic
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

session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}
include('odm-load.php');
include('udf_functions.php');

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

/*$_GET['where']='department_only';
  $_GET['keyword']='Information Systems';
  $_SESSION['uid']=102;
  $_GET['submit']='submit';
  $_GET['exact_phrase']='on';
  $_GET['case_sensitivity']='';
*/
/// includes
$start_time = time();
draw_header(msg('search'), $last_message);

echo '<body bgcolor="white">';
if(!isset($_GET['submit']))
{
    ?>
    <p>

    <table border="0" cellspacing="5" cellpadding="5">
        <form action=<?php echo $_SERVER['PHP_SELF']; ?> method="get">

            <tr>
                <td valign="top"><b><?php echo msg('label_search_term');?></b></td>
                <td><input type="Text" name="keyword" size="50"></td>
            </tr>
            <tr>
                <td valign="top"><b><?php echo msg('search');?></b></td>
                <td><select name="where">
                        <option value="author_only"><?php echo msg('author');?> (Last_name  First_name)</option>
                        <option value="department_only"><?php echo msg('department');?></option>
                        <option value="category_only"><?php echo msg('category');?></option>
                        <option value="descriptions_only"><?php echo msg('label_description');?></option>
                        <option value="filenames_only"><?php echo msg('label_filename');?></option>
                        <option value="comments_only"><?php echo msg('label_comment');?></option>
                        <option value="file_id_only"><?php echo msg('file');?> #</option>
                            <?php
                            udf_functions_search_options();
                            ?>
                        <option value="all" selected><?php echo msg('all');?></option>
                    </select></td>
            </tr>

            <tr>
                <td><?php echo msg('label_exact_phrase');?>: <input type="checkbox" name="exact_phrase"></td>
                <td><?php echo msg('label_case_sensitive'); ?><input type="checkbox" name="case_sensitivity"></td>
            </tr>
            <tr>
                <td>
                    <div class="buttons"><button class="positive" type="Submit" name="submit" value="Search"><?php echo msg('search');?></button></div>
                </td>
            </tr>
        </form>
    </table>

    <?php
    //echo '<br><b>Load Time: ' . time() - $start_time;
    draw_footer();

}
else
{
    function search($lwhere, $lkeyword, $lexact_phrase, $lcase_sensitivity, $lsearch_array)
    {
        $lequate = '=';
        $l_remain ='';
        if( $lexact_phrase != 'on' )
        {
            $lkeyword = '%' . $lkeyword . '%';
        }
        if($lcase_sensitivity != 'on')
        {
            $lequate = ' LIKE ';
        }
        else
        {
            $lequate = ' COLLATE latin1_general_cs LIKE ';
        }

        $lkeyword = addslashes($lkeyword);

        $lquery_pre = "SELECT {$GLOBALS['CONFIG']['db_prefix']}data.id FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$GLOBALS['CONFIG']['db_prefix']}user, {$GLOBALS['CONFIG']['db_prefix']}department, {$GLOBALS['CONFIG']['db_prefix']}category";
        $lquery = " WHERE {$GLOBALS['CONFIG']['db_prefix']}data.owner = {$GLOBALS['CONFIG']['db_prefix']}user.id
					AND {$GLOBALS['CONFIG']['db_prefix']}data.department={$GLOBALS['CONFIG']['db_prefix']}department.id 
					AND {$GLOBALS['CONFIG']['db_prefix']}data.category = {$GLOBALS['CONFIG']['db_prefix']}category.id AND (";
        $larray_len = sizeof($lsearch_array);
        switch($lwhere)
        {
            // Put all the category for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the category array are synchronized.
            case 'author_locked_files':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'data.status' . $lequate  . '\'' . $lkeyword . '\' AND '.$GLOBALS['CONFIG']['db_prefix'].'data.owner=\'' . $_SESSION['uid'] . '\'';
                break;

            // Put all the category for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the category array are synchronized.
            case 'category_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'category.name' . $lequate  . '\'' . $lkeyword . '\'';
                break;
            // Put all the author name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the author name array are synchronized.
            case 'author_only':
                if( $lexact_phrase=='on' )
                {
                    $lquery .= $GLOBALS['CONFIG']['db_prefix'].'user.first_name' . $lequate . '\'' . substr($lkeyword, strpos($lkeyword, ' ')+1 ) . '\' AND ' . $GLOBALS['CONFIG']['db_prefix'].'user.last_name' . $lequate . '\'' . substr($lkeyword, 0, strpos($lkeyword, ' ')) . '\'';
                }
                else
                {
                    $lquery .= $GLOBALS['CONFIG']['db_prefix'].'user.first_name' . $lequate  . '\'' . $lkeyword . '\' OR ' . $GLOBALS['CONFIG']['db_prefix'].'user.last_name' . $lequate . '\'' . $lkeyword . '\'';
                }
                break;
            // Put all the department name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the department name array are synchronized.case 'department_only':
            case 'department_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'department.name' . $lequate  . '\'' . $lkeyword . '\'';
                break;
            // Put all the description for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the description array are synchronized.
            case 'descriptions_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'data.description' . $lequate  . '\'' . $lkeyword . '\'';
                break;
            // Put all the file name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the file name array are synchronized.
            case 'filenames_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'data.realname' . $lequate . '\'' . $lkeyword . '\'';
                break;
            // Put all the comments for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the comments array are synchronized.
            case 'comments_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'data.comment' . $lequate  . '\'' . $lkeyword . '\'';
                break;
            case 'file_id_only':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'data.id' . $lequate . '\'' . $lkeyword . '\'';
                break;
            case 'all':
                $lquery .= $GLOBALS['CONFIG']['db_prefix'].'category.name' . $lequate  . '\'' . $lkeyword . '\' OR ' .
                        $GLOBALS['CONFIG']['db_prefix'].'user.first_name' . $lequate  . '\'' . $lkeyword . '\' OR ' . $GLOBALS['CONFIG']['db_prefix'].'user.last_name ' . $lequate  . '\'' . $lkeyword . '\' OR ' .
                        $GLOBALS['CONFIG']['db_prefix'].'department.name' . $lequate  . '\'' . $lkeyword . '\' OR ' .
                        $GLOBALS['CONFIG']['db_prefix'].'data.description' . $lequate  . '\'' . $lkeyword . '\' OR ' .
                        $GLOBALS['CONFIG']['db_prefix'].'data.realname' . $lequate  . '\'' . $lkeyword . '\' OR ' .
                        $GLOBALS['CONFIG']['db_prefix'].'data.comment' . $lequate  . '\'' . $lkeyword . '\'';
                break;

            default :
                list($lquery_pre,$lquery) = udf_functions_search($lwhere,$lquery_pre,$lquery,$lequate,$lkeyword);
                break;

        }
        $lquery .= ") ORDER BY {$GLOBALS['CONFIG']['db_prefix']}data.id ASC";
        $lresult = mysql_query($lquery_pre.$lquery);

        $lindex = 0;
        $lid_array = array();
        $llen = mysql_num_rows($lresult);
        while( $lindex < $llen )
        {
            list($lid_array[$lindex++]) = mysql_fetch_row($lresult);
        }
        if(@$l_remain != '' && $lexact_phrase != "on")
        {
            return array_values( array_unique( array_merge($lid_array, search($lwhere, substr($l_remain, 1), $lexact_phrase, $lcase_sensitivity, $lsearch_array) ) ) );
        }
        return array_values( array_intersect($lid_array, $lsearch_array) );
    }
    $current_user = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    $user_perms = new User_Perms($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    $current_user_permission = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
    //$s_getFTime = getmicrotime();
    if($_GET['where'] == 'author_locked_files')
    {
        $view_able_files_id = $current_user->getExpiredFileIds();
    }
    else
    {
        $view_able_files_id = $current_user_permission->getViewableFileIds();
    }
    //$e_getFTime = getmicrotime();
    $id_array_len = sizeof($view_able_files_id);
    $query_array = array();
    $search_result = search(@$_GET['where'], @$_GET['keyword'], @$_GET['exact_phrase'], @$_GET['case_sensitivity'], $view_able_files_id);
    //echo 'khoa' . sizeof($search_result);
    $sorted_result = my_sort($search_result);

    // Call the plugin API
    callPluginMethod('onSearch');

    list_files($sorted_result,  $current_user_permission, $GLOBALS['CONFIG']['dataDir'], false,false);
    echo '<br />';
    draw_footer();
    //echo '<br> <b> Load Page Time: ' . (getmicrotime() - $start_time) . ' </b>';
    //echo '<br> <b> Load Permission Time: ' . ($e_getFTime - $s_getFTime) . ' </b>';
}