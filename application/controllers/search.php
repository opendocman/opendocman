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

// (C) 2002-2007 Stephen Lawrence Jr., Khoa Nguyen, Jon Miner
// Main search logic
use Aura\Html\Escaper as e;

session_start();

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

/*$_GET['where']='department';
  $_GET['keyword']='Information Systems';
  $_SESSION['uid']=102;
  $_GET['submit']='submit';
  $_GET['exact_phrase']='on';
  $_GET['case_sensitivity']='';
*/

$start_time = time();
draw_header(msg('search'), $last_message);
ob_start();

if (!isset($_GET['submit'])) {
    ?>
    <form action="search" method="get" class="p-3">
        <div class="row mb-3">
            <div class="col-12 col-md-3 col-form-label fw-bold"><?php echo msg('label_search_term'); ?></div>
            <div class="col-12 col-md-9">
                <input type="text" name="keyword" class="form-control" placeholder="<?php echo msg('label_search_term'); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 col-md-3 col-form-label fw-bold"><?php echo msg('search'); ?></div>
            <div class="col-12 col-md-9">
                <select name="where" class="form-select">
                    <option value="author"><?php echo msg('author'). "(".msg('label_last_name')." ".msg('label_first_name').")"; ?></option>
                    <option value="department"><?php echo msg('department'); ?></option>
                    <option value="category"><?php echo msg('category'); ?></option>
                    <option value="descriptions"><?php echo msg('label_description'); ?></option>
                    <option value="filenames"><?php echo msg('label_filename'); ?></option>
                    <option value="comments"><?php echo msg('label_comment'); ?></option>
                    <option value="file_id"><?php echo msg('file'); ?> #</option>
                    <?php udf_functions_search_options(); ?>
                    <option value="all" selected><?php echo msg('searchpage_all_meta'); ?></option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="exact_phrase" class="form-check-input" id="exact_phrase">
                    <label class="form-check-label" for="exact_phrase"><?php echo msg('label_exact_phrase'); ?></label>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="case_sensitivity" class="form-check-input" id="case_sensitivity">
                    <label class="form-check-label" for="case_sensitivity"><?php echo msg('label_case_sensitive'); ?></label>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="form-check">
                    <input type="checkbox" name="search_content" class="form-check-input" id="search_content">
                    <label class="form-check-label" for="search_content"><?php echo msg('label_search_file_contents'); ?></label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <button type="submit" name="submit" value="Search" class="btn btn-primary"><?php echo msg('search'); ?></button>
            </div>
        </div>
    </form>

    <?php
    //echo '<br><b>Load Time: ' . time() - $start_time;
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
} else {
    function search($where, $keyword, $exact_phrase, $case_sensitivity, $search_array)
    {
        global $pdo;

        $remain ='';
        if ($exact_phrase != 'on') {
            $keyword = '%' . $keyword . '%';
        }
        if ($case_sensitivity != 'on') {
            $equate = ' LIKE ';
        } else {
            $equate = ' LIKE BINARY ';
        }

        $query_pre = "
          SELECT
            d.id
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}data as d,
            {$GLOBALS['CONFIG']['db_prefix']}user as u,
            {$GLOBALS['CONFIG']['db_prefix']}department dept,
            {$GLOBALS['CONFIG']['db_prefix']}category as c ";

        $query = "
            WHERE
                d.owner = u.id
            AND
                d.department = dept.id
            AND
                d.category = c.id AND (
        ";

        $author_first_name = '';
        $author_last_name = '';
        $use_uid = false;
        switch ($where) {
            // Put all the category for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the category array are synchronized.
            case 'author_locked_files':
                $use_uid = true;
                $query .= "d.status $equate :keyword AND d.owner = :uid ";
                break;

            // Put all the category for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the category array are synchronized.
            case 'category':
                $query .= "c.name $equate :keyword ";
                break;
            // Put all the author name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the author name array are synchronized.
            case 'author':
                if ($exact_phrase=='on') {
                    $author_first_name = substr($keyword, strpos($keyword, ' ') +1);
                    $author_last_name = substr($keyword, 0, strpos($keyword, ' '));
                    $query .= " u.first_name $equate :author_first_name AND u.last_name  $equate :author_last_name ";
                } else {
                    $query .= " u.first_name $equate  :keyword OR u.last_name $equate :keyword ";
                }
                break;
            // Put all the department name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the department name array are synchronized.case 'department':
            case 'department':
                $query .= "dept.name $equate  :keyword ";
                break;
            // Put all the description for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the description array are synchronized.
            case 'descriptions':
                $query .= "d.description $equate :keyword ";
                break;
            // Put all the file name for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the file name array are synchronized.
            case 'filenames':
                $query .= "d.realname $equate :keyword ";
                break;
            // Put all the comments for each of the OBJ in the OBJ array into an array
            // Notice, the index of the OBJ_array and the comments array are synchronized.
            case 'comments':
                $query .= "d.comment $equate :keyword ";
                break;
            case 'file_id':
                $query .= "d.id $equate :keyword ";
                break;
            case 'all':
                $query .= "c.name $equate  :keyword OR " .
                        "u.first_name $equate :keyword OR u.last_name $equate :keyword OR " .
                        "dept.name $equate :keyword OR " .
                        "d.description $equate :keyword OR " .
                        "d.realname $equate :keyword OR " .
                        "d.comment $equate :keyword ";
                break;

            default :
                list($query_pre, $query) = udf_functions_search($where, $query_pre, $query, $equate, $keyword);
                break;

        }

        $query .= ") ORDER BY d.id ASC";

        $final_query = $query_pre . $query;

        $stmt = $pdo->prepare($final_query);
        
        if (!empty($use_uid)) {
            $stmt->bindParam(':uid', $_SESSION['uid']);
            $stmt->bindParam(':keyword', $keyword);
        } elseif (!empty($author_last_name) && $exact_phrase == 'on') {
            $stmt->bindParam(':author_first_name', $author_first_name);
            $stmt->bindParam(':author_last_name', $author_last_name);
        } else {
            $stmt->bindParam(':keyword', $keyword);
        }

        $stmt->execute();
        $result = $stmt->fetchAll();

        $index = 0;
        $id_array = array();

        foreach ($result as $row) {
            $id_array[$index++] = $row['id'];
            $index++;
        }
        if (@$remain != '' && $exact_phrase != "on") {
            return array_values(array_unique(array_merge($id_array, search($where, substr($remain, 1), $exact_phrase, $case_sensitivity, $search_array))));
        }
        return array_values(array_intersect($id_array, $search_array));
    }
    try {
        $current_user = new User($_SESSION['uid'], $pdo);
    } catch (Exception $e) {
        error_log("Search.php - Error creating user objects: " . $e->getMessage());
        error_log("Search.php - Session UID: " . (isset($_SESSION['uid']) ? $_SESSION['uid'] : 'NOT SET'));
        header('Location: error?ec=1&last_message=' . urlencode('User initialization failed'));
        exit;
    }

    // Call the plugin API
    callPluginMethod('onSearch');

    $GLOBALS['smarty']->assign('state', 1);
    display_smarty_template('out.tpl');
    $content = ob_get_clean();
    $GLOBALS['smarty']->assign('content', $content);
    display_smarty_template('_content.tpl');
    draw_footer();
}
