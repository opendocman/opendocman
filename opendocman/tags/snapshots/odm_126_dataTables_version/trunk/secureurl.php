<?php
/*
secureurl.php - provides integration to secure url class
Copyright (C) 2002, 2003, 2004 Stephen Lawrence Jr., Khoa Nguyen
Copyright (C) 2005-2011 Stephen Lawrence Jr.

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

if($GLOBALS['CONFIG']['secureurl'] == 'True' && (isset($_GET['id']) || isset($_GET['state']) || isset($_GET['id0']) || isset($_GET['where']) || isset($_GET['sort_order']) || isset($_GET['submit']) ) )
{
    $secureurl = new phpsecureurl;
    header('Location:' . $secureurl->encode("{$_SERVER['SCRIPT_NAME']}?{$_SERVER['QUERY_STRING']}"));
    exit;
}
elseif(isset($_GET['aku']))
{
    $secureurl = new phpsecureurl;
    $secureurl->decode();
    //echo 'dkakdkdk'.$_REQUEST['id'];
    //echo("Location:$_SERVER[SCRIPT_NAME]?" . $_SERVER['QUERY_STRING']); exit;
}