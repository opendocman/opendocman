<?php
/*
All source code copyright and proprietary Melonfire, 2001. All content, brand names and trademarks copyright and proprietary Melonfire, 2001. All rights reserved. Copyright infringement is a violation of law.

This source code is provided with NO WARRANTY WHATSOEVER. It is meant for illustrative purposes only, and is NOT recommended for use in production environments. 

Read more articles like this one at http://www.melonfire.com/community/columns/trog/ and http://www.melonfire.com/
*/

// error.php - displays error messages based on error code $ec

// includes
include('config.php');
session_start();
draw_header('Error');
if (!session_is_registered('uid'))
{
        draw_menu();
}
else
{
        draw_menu($_SESSION['uid']);
}

@draw_status_bar('Error', $_REQUEST['last_message']);

switch ($_REQUEST['ec'])
{
// login failure
case 0:
$message = 'There was an error logging you in. <a href="'.$GLOBALS['CONFIG']['base_url'].'">Please try again.</a>';
break;

// session problem
case 1:
$message = 'Please <a href='.$GLOBALS['CONFIG']['base_url'].'>log in</a> again.';
break;

// malformed variable/failed query
case 2:
$message = 'There was an error performing the requested action. Please <a href='.$GLOBALS['CONFIG']['base_url'].'>log in</a> again.';
break;

// User already exists
case 3:
$message = 'Record already exists. Try again with a different value.';
break;

// User not admin
case 4:
$message = 'You are not an administrator. <a href=out.php>Back</a>';
break;

// Category exists
case 5:
$message = 'Category '.$_REQUEST['category'].' already exists! <a href=out.php>Back</a>';
break;

// Input Field Blank
case 6:
$message = 'You did not enter a value! <a href=out.php>Back</a>';
break;


// file not uploaded
case 11:
$message = 'Please upload a valid document.';
break;

// rights not assigned
case 12:
$message = 'You must assign view/modify rights to at least one user.';
break;

// illegal file type
case 13:
$message = 'That file type is not currently supported.<p>Please upload a document conforming to any of the following file types:<br><ul align=left>';
echo "_File array is " . array_values($_FILES['file']);
	foreach($GLOBALS['allowedFileTypes'] as $this)
	{
		$message .= '<li>'.$this;
	}
$message .= '</ul>';
break;
case 14:
$message = 'Non-unique account.  Please contact '.$GLOBALS['CONFIG']['site_mail'].' for help.';
break;
case 15:
$message = 'Error: wrong file!  Please check in the right file.';
break;
case 16: 
$message = 'Non-unique key field in database.';
break;
case 17: 
$message = 'This file cannot be checked in';
break;
default:
$message = 'There was an error performing the requested action. Please <a href='.$GLOBALS['CONFIG']['base_url'].'>log in</a> again.';
break;
case 18:
$message = 'This file cannot be uploaded propertly';
break;
case 19:
$message = 'You do not currently have an account. Please contact <a href="mailto:' . $GLOBALS['CONFIG']['site_mail'] . '"> ' . $GLOBALS['CONFIG']['site_mail'] . '</a> to request one.';
break;

}
echo($message);
//echo 'Please try to <a href="'.$GLOBALS['CONFIG']['base_url'].'">Log-in</a> again.';
draw_footer();
?>
