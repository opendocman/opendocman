<?php
session_start();
if (!session_is_registered('SESSION_UID'))
{
        header('Location:error.php?ec=1');
        exit;
}
include ('./config.php');
// includes
$connection = mysql_connect($hostname, $user, $pass) or die ("Unable to connect!");

if(!$starting_index)
{
        $starting_index = 0;
}

if(!$stoping_index)
{
        $stoping_index = $starting_index+$GLOBALS['CONFIG']['page_limit']-1;
}

if(!$sort_by)
{
        $sort_by = 'id';
}

if(!$sort_order)
{
        $sort_order = 'a-z';
}

if(!$page)
{
        $page = 0;
}

$with_caption = false;

if(!$submit)
{
        draw_menu($SESSION_UID);
        draw_header('Rejected Files');
        draw_status_bar('Rejected Document Listing', $last_message);
        $page_url = $_SERVER['PHP_SELF'] . '?';

        $user_obj = new User($SESSION_UID, $connection, $database);
        $userperms = new UserPermission($SESSION_UID, $connection, $database);
        $fileobj_array = $user_obj->getRejectedFiles();
        $sorted_obj_array = obj_array_sort_interface($fileobj_array, $sort_order, $sort_by);
        echo '<FORM name="table" method="POST" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";

?>	
                <TABLE border="1"><TR><TD>

<?php

                list_files($sorted_obj_array, $userperms, $page_url, $GLOBALS['CONFIG']['dataDir'], $sort_order,  $sort_by, $starting_index, $stoping_index, true, $with_caption);
        list_nav_generator(sizeof($sorted_obj_array), $GLOBALS['CONFIG']['page_limit'], $page_url, $current_page, $sort_by, $sort_order);

?>

                </TD></TR><TR><TD><CENTER><INPUT type="SUBMIT" name="submit" value="Re-Submit For Review"><INPUT type="submit" name="submit" value="Delete file(s)">
                </TABLE></FORM>

<?php

}
elseif($submit=='Re-Submit For Review')
{
        for($i = 0; $i<$num_checkboxes; $i++)
                if($HTTP_POST_VARS["checkbox$i"])
                {
                        $fileid = $HTTP_POST_VARS["checkbox$i"];
                        $file_obj = new FileData($fileid, $connection, $database);
                        //$user_obj = new User($file_obj->getOwner(), $connection, $database);
                        //$mail_to = $user_obj->getEmailAddress();									
                        //mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
                        $file_obj->Publishable(0);
                }
        header('Location:' . $_SERVER['PHP_SELF'] . '?last_message=File authorization completed successfully');
}
elseif($submit=='Delete file(s)')
{
        $url = 'delete.php?';
        $id = 0;
        for($i = 0; $i<$num_checkboxes; $i++)
                if($HTTP_POST_VARS["checkbox$i"])
                {
                        $fileid = $HTTP_POST_VARS["checkbox$i"];
                        $url .= 'id'.$id.'='.$fileid.'&';
                        $id ++;
                }
        $url = substr($url, 0, strlen($url)-1);
        header('Location:'.$url.'&num_checkboxes='.$num_checkboxes);
}
?>
