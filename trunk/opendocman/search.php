<?php
/*$where='department_only';
  $keyword='Information Systems';
  $SESSION_UID=102;
  $submit='submit';
 */
session_start();
if (!session_is_registered('SESSION_UID'))
{
        header('Location:error.php?ec=1');
        exit;
}
/// includes
include('config.php');

draw_header('Search');
draw_menu($SESSION_UID);
draw_status_bar('Search', "");

if(!isset($starting_index))
{
        $starting_index = 0;
}
if(!isset($stoping_index))
{
        $stoping_index = $starting_index+$GLOBALS['CONFIG']['page_limit']-1;
}
if(!isset($sort_by))
{
        $sort_by = 'id';
}
if(!isset($sort_order))
{
        $sort_order = 'a-z';
}
if(!isset($page))
{
        $page = 0;
}

echo '<body bgcolor="white">';

if(!isset($submit))
{
        ?>
                <center>
                <p>

                 <table border="0" cellspacing="5" cellpadding="5">
                  <form action=<?php echo $_SERVER['PHP_SELF']; ?> method="get">

                   <tr>
                    <td valign="top"><b>Search term</b></td>
                    <td><input type="Text" name="keyword" size="50"></td>
                    <td>Exact Word: <input type="checkbox" name="exact_word"></td>
                    <td>Case Sensitivity <input type="checkbox" name="case_sensitivity"></td>
                   </tr>
                   <tr>
                    <td valign="top"><b>Search</b></td>
                    <td><select name="where">
                      <option value="author_only">Author only</option>
                      <option value="department_only">Department only</option>
                      <option value="category_only">Category only</option>
                      <option value="descriptions_only">Descriptions only</option>
                      <option value="filenames_only">Filenames only</option>
                      <option value="comments_only">Comments only</option>
                      <option value="all" selected>All</option>
                    </select></td>
                 </tr>

                <tr>
                  <td colspan="2" align="center">
                    <input type="Submit" name="submit" value="Search">
                    <input type="hidden" name="submit" value="Search">
                  </td>
                </tr>

               </form>
              </table>
            </center>

<?php

draw_footer();

}
else
{
        sort_browser();
        /*

         */
        function OBJs_search_interface($where, $query, $exact_word, $case_sensitivity, $OBJ_array)
        {
                if($where == 'all')
                {
                        $cases = array('filenames_only', 'descriptions_only', 'comments_only', 'author_only', 'department_only', 'category_only');
                        $cases_len = sizeof($cases);
                        for($i = 0; $i<$cases_len; $i++)
                        {
                                $query_array = OBJs_to_strs($cases[$i],  $query, $exact_word, $case_sensitivity, $OBJ_array);
                                $search_result = str_search($query, $query_array, $exact_word, $case_sensitivity);
                                for($j = 0; $j < sizeof($search_result); $j++)
                                        $search_result[$j] = array($search_result[$j], $cases[$i]);
                                if($i == 0)
                                {
                                        $result_array = $search_result;
                                }
                                else
                                {
                                        $result_array = merge2DArrays($result_array, $search_result, true, 0);
                                }	
                        }
                        $search_result = $result_array;
                }
                else
                {
                        $query_array = OBJs_to_strs($where, $query, $exact_word, $case_sensitivity, $OBJ_array);
                        $search_result = str_search($query, $query_array, $exact_word, $case_sensitivity);
                        for($j = 0; $j < sizeof($search_result); $j++)
                                $search_result[$j] = array($search_result[$j], $cases[$i]);
                }	
                $search_result_len = sizeof($search_result);
                $sorted_result = array();
                $sorted_result_index = 0;
                for($i = 0; $i<$search_result_len; $i++)
                {
                        if($search_result[$i][0] == true)
                        {
                                $sorted_result[$sorted_result_index++] = $OBJ_array[$i];
                        }
                }
                return $sorted_result;
        }

        /*
           OBJ_array_search search for the string query in the where field of every data obj.
Ex: $where='category_only', $query='test', $exact_word=true, $case_sensitivity=true, $OBJ_array
OBJ_array_search would put all the categories of the all the element in the OBJ_array into an array;
then, OBJ_array_search would submit this string into the interface along with $where, $query, and ...

Ex: $where='all', ..., OBJ_array
OBJ_array_search will called itself to do a search in every supported fields.

Return: OBJ_array_search will return a 2D array. return_array[index1][index2] where
index1 points to data for an object and index2 points to the OBJ it self or the field that 
the OBJ statisfied.  Let's OBJ1 was found in the by doing a search on filename_only
then return_array[index1] = (OBJ1, 'filename_only'); 
         */
        function OBJs_to_strs($where, $query, $exact_word, $case_sensitivity, $OBJ_array)
        {
                $obj_array_len = sizeof($OBJ_array);
                //all search cases supported by this search engine.
                //use to perform an all search.
                $cases = array('filenames_only', 'descriptions_only', 'comments_only', 'author_only', 'department_only', 'category_only');
                $user_obj = new User(1, $OBJ_array[0]->connection, $OBJ_array[0]->database);
                $dept_obj = new Department(1, $OBJ_array[0]->connection, $OBJ_array[0]->database);
                switch($where)
                {
                        // Put all the category for each of the OBJ in the OBJ array into an array
                        // Notice, the index of the OBJ_array and the category array are synchronized.
                        case 'category_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $query_array[$i] = $OBJ_array[$i]->getCategoryName();
                                }
                                break;
                                // Put all the author name for each of the OBJ in the OBJ array into an array
                                // Notice, the index of the OBJ_array and the author name array are synchronized.
                        case 'author_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $query_array[$i] = $OBJ_array[$i]->getOwnerName();
                                }
                                break;

                                // Put all the department name for each of the OBJ in the OBJ array into an array
                                // Notice, the index of the OBJ_array and the department name array are synchronized.case 'department_only':
                        case 'department_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $dept_obj->setId($OBJ_array[$i]->getDepartment());
                                        $query_array[$i] = $dept_obj->getName();
                                }
                                break;
                                // Put all the description for each of the OBJ in the OBJ array into an array
                                // Notice, the index of the OBJ_array and the description array are synchronized.
                        case 'descriptions_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $query_array[$i] = $OBJ_array[$i]->getDescription();
                                }
                                break;
                                // Put all the file name for each of the OBJ in the OBJ array into an array
                                // Notice, the index of the OBJ_array and the file name array are synchronized.
                        case 'filenames_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $query_array[$i] = $OBJ_array[$i]->getName();
                                }
                                break;
                                // Put all the comments for each of the OBJ in the OBJ array into an array
                                // Notice, the index of the OBJ_array and the comments array are synchronized.
                        case 'comments_only':
                                for($i = 0; $i<$obj_array_len; $i++)
                                {
                                        $query_array[$i] = $OBJ_array[$i]->getComment();
                                }
                                break;
                        default : break;
                }
                return $query_array;
        }
        $current_user = new User($SESSION_UID, $connection, $database);
        $user_perms = new User_Perms($SESSION_UID, $connection, $database);
        $current_user_permission = new UserPermission($SESSION_UID, $connection, $database);
        $view_able_files_obj = $current_user_permission->getAllowedFileOBJs();
        $obj_array_len = sizeof($view_able_files_obj);
        $query_array = array();
        $search_result = OBJs_search_interface($where, $keyword, $exact_word, $case_sensitivity, $view_able_files_obj);
        /*if($where == 'all')
          {
          $array_len = sizeof($search_result);
          for($i = 0; $i<$array_len; $i++)
          {
          $search_result[$i] = $search_result[$i][0];
          }
          }*/
        $page_url = $_SERVER['PHP_SELF'].'?keyword='.$keyword.'&where='.$where.'&submit='.$submit;
        $sorted_obj_array = obj_array_sort_interface($search_result, $sort_order, $sort_by);
        list_files($sorted_obj_array,  $current_user_permission, $page_url,  $GLOBALS['CONFIG']['dataDir'], $sort_order, $sort_by, $starting_index, $stoping_index);
        echo '<BR>';
        list_nav_generator(sizeof($sorted_obj_array), $GLOBALS['CONFIG']['page_limit'], $page_url,$page, $sort_by, $sort_order );

        draw_footer();
}

