<?php
/*
rejects.php - Show rejected files
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2010 Stephen Lawrence Jr.

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
if (!isset ($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
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
    draw_header(msg('message_documents_rejected'));
    @draw_status_bar(msg('message_documents_rejected'), $_REQUEST['last_message']);
    $page_url = $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'];

    $user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
    $userperms = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
    if($user_obj->isAdmin() && @$_REQUEST['mode'] == 'root')
    {
        $fileid_array = $user_obj->getAllRejectedFileIds();
    }
    else
    {
        $fileid_array = $user_obj->getRejectedFileIds();
    }
    $sorted_id_array = my_sort($fileid_array, $_REQUEST['sort_order'], $_REQUEST['sort_by']);
    ?>
    <?php
    if(@$_REQUEST['mode']=='root')
    {
        echo '<FORM name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '?mode=root"' . ' onsubmit="closeWindow(1250);" method="POST">';
    }
    else
    {
        echo '<FORM name="author_note_form" action="' . $_SERVER['PHP_SELF'] . '" onsubmit="closeWindow(1000);" method="POST">';?>
}
<TABLE border="0"><TR><TD>

                    <?php

                    $list_status = list_files($sorted_id_array, $userperms, $page_url, $GLOBALS['CONFIG']['dataDir'], $_REQUEST['sort_order'],  $_REQUEST['sort_by'], $_REQUEST['starting_index'], $_REQUEST['stoping_index'], true, $with_caption);
                    list_nav_generator(sizeof($sorted_id_array), $GLOBALS['CONFIG']['page_limit'], $GLOBALS['CONFIG']['num_page_limit'], $page_url, $_REQUEST['page'], $_REQUEST['sort_by'], $_REQUEST['sort_order']);
                    ?>
        </TD></TR>
            <?php
            if($list_status != -1)
            {?>
    <input type="hidden" name="action" value="resubmit" />
    <TR><TD><CENTER><INPUT type="submit" name="submit" value="<?php echo msg('button_resubmit_for_review'); ?>"><INPUT type="submit" name="submit" value="<?php echo msg('button_delete');?>">
                            <?php } ?>
                </TABLE></FORM>

                        <?php
                        draw_footer();
                    }
                    elseif($_POST['action'] == 'resubmit')
                    {
                        for($i = 0; $i < $_POST['num_checkboxes'];$i++)
                        {
                            if(isset($_POST["checkbox$i"]))
                            {
                                $fileid = $_POST["checkbox$i"];
                                $file_obj = new FileData($fileid, $GLOBALS['connection'], $GLOBALS['database']);
                                //$user_obj = new User($file_obj->getOwner(), $connection, $GLOBALS['database']);
                                //$mail_to = $user_obj->getEmailAddress();
                                //mail($mail_to, $mail_subject. $file_obj->getName(), ($mail_greeting.$file_obj->getName().' '.$mail_body.$mail_salute), $mail_headers);
                                $file_obj->Publishable(0);
                            }
                        }
                        header('Location:' . $_SERVER['PHP_SELF'] . '?mode=' . @$_REQUEST['mode'] . '&last_message='. msg('message_file_authorized'));
                    }
                    elseif($_POST['action'] == msg('button_delete'))
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