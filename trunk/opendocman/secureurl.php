<?php
if(isset($_GET['id']) || isset($_GET['state']) || isset($_GET['id0']) || isset($_GET['where']) || isset($_GET['sort_order']) || isset($_GET['submit']))
{
	$secureurl = new phpsecureurl;
	header('Location:' . $secureurl->encode("$_SERVER[SCRIPT_NAME]?$HTTP_SERVER_VARS[QUERY_STRING]"));
	exit;
}
elseif(isset($_GET['aku']))
{
 $secureurl = new phpsecureurl;	
 $secureurl->decode();
 //echo 'dkakdkdk'.$_REQUEST['id'];
 //echo("Location:$_SERVER[SCRIPT_NAME]?" . $HTTP_SERVER_VARS['QUERY_STRING']); exit;
}
