<?php
session_start();
if (!session_is_registered('uid'))
{
        header('Location:index.php?redirection=' . urlencode( $_SERVER['REQUEST_URI']) );
		exit;
}
include ('./config.php');
// includes
if(!isset($_REQUEST['starting_index']))
{
        $_REQUEST['starting_index'] = 0;
}

if(!isset($_REQUEST['stoping_index']))
{
        $_REQUEST['stoping_index'] = $_REQUEST['starting_index']+$GLOBALS['CONFIG']['page_limit']-1;
}

if(!isset($_REQUEST['sort_by']))
{
        $_REQUEST['sort_by'] = 'id';
}

if(!isset($_REQUEST['sort_order']))
{
        $_REQUEST['sort_order'] = 'asc';
}

if(!isset($_REQUEST['page']))
{
        $_REQUEST['page'] = 0;
}

$with_caption = false;

if(!isset($_POST['submit']))
{
        draw_menu($_SESSION['uid']);
        draw_header('Rejected Files');
        @draw_status_bar('Rejected Document Listing', $_REQUEST['last_message']);
        $page_url = $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'];

        $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
        $userperms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
		if($user_obj->isRoot() && @$_REQUEST['mode'] == 'root')
			$fileid_array = $user_obj->getAllRejectedFileIds();
		else
			$fileid_array = $user_obj->getRejectedFileIds();
        $sorted_id_array = my_sort($fileid_array, $_REQUEST['sort_order'], $_REQUEST['sort_by']);
		?>
		 <?php
			 if(@$_REQUEST['mode']=='root')
				 echo '<FORM name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '?mode=root"' . ' onsubmit="closeWindow(1250);" method="POST">';
			 else
				 echo '<FORM name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '" onsubmit="closeWindow(1000);" method="POST">';?>
                <TABLE border="1"><TR><TD>

<?php

        list_files($sorted_id_array, $userperms, $page_url, $GLOBALS['CONFIG']['dataDir'], $_REQUEST['sort_order'],  $_REQUEST['sort_by'], $_REQUEST['starting_index'], $_REQUEST['stoping_index'], true, $with_caption);
        list_nav_generator(sizeof($sorted_id_array), $GLOBALS['CONFIG']['page_limit'], $GLOBALS['CONFIG']['num_page_limit'], $page_url, $_REQUEST['page'], $_REQUEST['sort_by'], $_REQUEST['sort_order']);
?>
                </TD></TR><TR><TD><CENTER><INPUT type="SUBMIT" name="submit" value="Re-Submit For Review"><INPUT type="submit" name="submit" value="Delete file(s)">
                </TABLE></FORM>

<?php

}
elseif($_POST['submit'] == 'Re-Submit For Review')
{
        for($i = 0; $i < $_POST['num_checkboxes'];$i++)
                if(isset($_POST["checkbox$i"]))
                {
                        $fileid = $_POST["checkbox$i"];
                        $file_obj = new FileData($fileid, $GLOBALS['connection'], $GLOBALS['database']);
                        //$user_obj = new User($file_obj->getOwner(), $connection, $GLOBALS['database']);
                        //$mail_to = $user_obj->getEmailAddress();									
                        //mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
                        $file_obj->Publishable(0);
                }
        header('Location:' . $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'] . '&last_message=File authorization completed successfully');
}
elseif($_POST['submit']=='Delete file(s)')
{
        $url = 'delete.php?mode=tmpdel&';
        $id = 0;
        for($i = 0; $i<$_POST['num_checkboxes']; $i++)
                if(isset($_POST["checkbox$i"]))
                {
                        $fileid = $_POST["checkbox$i"];
                        $url .= 'id'.$id.'='.$fileid.'&';
                        $id ++;
                }
        $url = substr($url, 0, strlen($url)-1);
        header('Location:'.$url.'&num_checkboxes='.$_POST['num_checkboxes']);
}
?>
