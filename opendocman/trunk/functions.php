<?php
/*
functions.php - various utility functions
Copyright (C) 2002-2007 Stephen Lawrence, Khoa Nguyen, Jon Miner
Copyright (C) 2008-2009 Stephen Lawrence
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

//require_once ('config.php');

include_once('version.php');

require_once('includes/smarty/Smarty.class.php');
$GLOBALS['smarty'] = new Smarty();
$GLOBALS['smarty']->template_dir = 'templates/' . $GLOBALS['CONFIG']['theme'] .'/';

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
//require_once('includes/sanitize.inc.php');
if( !defined('function') )
{
  	define('function', 'true', false);
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
		{	return 0;	}
	}
	
	// Return a copy of $string where all the spaces are converted into underscores
	function space_to_underscore($string)
	{
	    $string_len = strlen($string);
		$index = 0;
		while( $index< $string_len )
		{
			if($string[$index] == ' ')
				$string[$index]= '_';
			$index++;
		}
		return $string;
	}
	// Draw the status bar for each page
	function draw_status_bar($message, $lastmessage='')
	{
		if(!isset($_REQUEST['state']))
			$_REQUEST['state']=1;
		echo "\n".'<!------------------begin_draw_status_bar------------------->'."\n";
		if (!isset ($message))
		{
			$message='Select';
		}
		echo '<link rel="stylesheet" type="text/css" href="' . $GLOBALS['CONFIG']['base_url'] . '/linkcontrol.css">'."\n";
		echo '<center>'."\n";
		echo '<table width="100%" border="0" cellspacing="0" cellpadding="5">'."\n";
		echo '<tr>'."\n";
		//echo '<td bgcolor="#0000A0" align="left" valign="middle" width="110">'."\n";
		//echo '<b><font size="-2" face="Arial" color="White">'."\n";
		//echo $message;
		//echo '</font></b></td>'."\n";
		echo '<td bgcolor="#0000A0" align="left" valign="middle" width="10">'."\n";
		echo '<a class="statusbar" href="' . $GLOBALS['CONFIG']['base_url'] . '/out.php" style="text-decoration:none">Home</a>'."\n</td>";
		echo '<td bgcolor="#0000A0" align="left" valign="middle" width="10">'."\n";
		echo '<a class="statusbar" href="' . $GLOBALS['CONFIG']['base_url'] . '/profile.php" style="text-decoration:none">Preferences</a>'."\n</td>";
		echo '<td bgcolor="#0000A0" align="left" valign="middle" width="10">'."\n";
		echo '<a class="statusbar" href="' . $GLOBALS['CONFIG']['base_url'] . '/help.html" onClick="return popup(this, \'Help\')" style="text-decoration:none">Help</a>'."\n</td>";
		?>	    <TD bgcolor="#0000A0" align="middle" valign="middle" width="0"><font size="3" face="Arial" color="White">|</FONT></TD>
			<TD bgcolor="#0000A0" align="left" valign="middle">
			<?php	$crumb = new crumb();
		$crumb->addCrumb($_REQUEST['state'], $message, $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);	
        $crumb->printTrail($_REQUEST['state']);
		echo '<td bgcolor="#0000A0" align="right" valign="middle">'."\n";
        if ( $lastmessage != "" )
        {
            echo '<b><font size="-2" face="Arial" color="White">';
            echo 'Last Message: '.$lastmessage;
            echo '</td>';
        }
		?>	    </font></b>
			</TD>
			</tr>
			</table>
			</center>

			<!------------------end_draw_status_bar------------------->
			<?php
	}
	function my_sort ($id_array, $sort_order = 'asc', $sort_by = 'id')
	{
		if(!isset($id_array[0]))
			return $id_array;
		if (sizeof($id_array) == 0 )
			return $id_array;
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
			list($array[$li]) = mysql_fetch_row($lresult);
		return  array_values( array_intersect($array, $id_array) );
	}

	// This function draws the menu screen
        function draw_menu($uid='')
        {
            echo "\n".'<!------------------begin_draw_menu------------------->'."\n";
            echo "\n".'<!------------------UID is ' . $uid . '------------------->'."\n";
            if($uid != NULL)
            {
            	$current_user_obj = new User($uid, $GLOBALS['connection'], $GLOBALS['database']);
            }
            echo '<table width="100%" cellspacing="0" cellpadding="0">'."\n";
            echo '<tr>'."\n";
            echo '<td align="left"><a href="' . $GLOBALS['CONFIG']['base_url'] . '/out.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/companylogo.gif" title="'.$GLOBALS['CONFIG']['title'].'" alt="'.$GLOBALS['CONFIG']['title'].'" border="0"></a></td>'."\n";
            echo '<td align="right" nowrap>'."\n";
            echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/in.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/check-in.png" title="Check In" alt="Check In" border=0></a>'."\n";
            echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/search.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/search.png" title="Search" alt="Search" border=0></a>'."\n";
            echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/add.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/add.png" title="Add" alt="Add" border="0"></a>'."\n";
           if($uid != NULL && $current_user_obj->isAdmin())
            {
                echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/admin.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/setting.png" alt="Administration" border="0"></a>'."\n";
            }
            echo '<a href="' . $GLOBALS['CONFIG']['base_url'] . '/logout.php"><img src="' . $GLOBALS['CONFIG']['base_url'] . '/images/logout.png" alt="Logout" border="0"></a>'."\n";
            echo '</td>'."\n";
            echo '</tr>'."\n";
            echo '</table>'."\n";
            echo "\n".'<!------------------end_draw_menu------------------->'."\n";
        }
	function draw_header($page_title)
    {
        if(is_dir('install'))
        {
            echo  '<span style="color: red;">Security Notice: You should remove the "install" folder before proceeding</span>';
        }

        $GLOBALS['smarty']->assign('page_title', $page_title);
        $GLOBALS['smarty']->display('header.tpl');
/*
		if (!isset($page_title))
		{
			$page_title='Main';
		}
		echo '<!---------------------------Start drawing header----------------------------->'."\n";
		echo '<html>'."\n";
		echo '	<HEAD>'."\n";
		echo '  	<TITLE>'.$GLOBALS['CONFIG']['title'].' - '.$page_title.'</TITLE>'."\n";
?>
		<SCRIPT TYPE="text/javascript">
		<!--
		function popup(mylink, windowname)
		{
			if (! window.focus)return true;
			var href;
			if (typeof(mylink) == 'string')
				href=mylink;
			else
				href=mylink.href;
			window.open(href, windowname, 'width=300,height=500,scrollbars=yes');
			return false;
		}
		//-->
		</SCRIPT>
<?php
		echo '	</HEAD>'."\n";
		echo '  	<body bgcolor="white">'."\n";
		echo '<!----------------------------End drawing header----------------------------->'."\n";
*/
	}

	function draw_error($message)
	{
		header ('Location:' . $message);
	}
	
	function draw_footer()
	{
        $GLOBALS['smarty']->display('footer.tpl');
/*
		echo "\n".'<!-------------------------------begin_draw_footer------------------------------>'."\n";
		echo '<hr>'."\n";
		echo ' <h5>'.$GLOBALS['CONFIG']['current_version'].'<BR>';
		echo '&copy; <a href="mailto:'.$GLOBALS['CONFIG']['site_mail'].'">'.$GLOBALS['CONFIG']['title'].'</a>'."\n";
		echo ' </body>'."\n";
		echo '</html>'."\n";
		echo '<!-------------------------------end_draw_footer------------------------------>'."\n";
*/
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
                        $OBJ_array[$i] = new User($user_ID_array[$i], $GLOBALS['connection'], $GLOBALS['database']);
                email_users_obj($mail_from, $OBJ_array, $mail_subject, $mail_body, $mail_header);
		}
		
		function getmicrotime(){ 
			list($usec, $sec) = explode(" ",microtime()); 
			return ((float)$usec + (float)$sec); 
		}
        function list_files($fileid_array, $userperms_obj, $page_url, $dataDir, $sort_order = 'asc', $sort_by = 'id', $starting_index = 0, $stoping_index = 5, $showCheckBox = 'false', $with_caption = 'false')
        {
           $secureurl= new phpsecureurl;
        	if(sizeof($fileid_array)==0 || !isset($fileid_array[0]))
            {
                echo'<img src="images/exclamation.gif"> No files found' . "\n";
                return -1;
            }
				echo "\n".'<!----------------------Table Starts----------------------->'."\n";
                $checkbox_index = 0;
                $count = sizeof($fileid_array);
                $css_td_class = "'listtable'";
                if($sort_order == 'asc')
                {
                        $sort_img = $GLOBALS['CONFIG']['base_url'] . '/images/icon_sort_az.gif';
                        $next_sort = 'desc';
                }
                else if($sort_order == 'desc')
                {
                        $sort_img = $GLOBALS['CONFIG']['base_url'] . '/images/icon_sort_za.gif';
                        $next_sort = 'asc';
                }
                else 
                {
                        $sort_img = $GLOBALS['CONFIG']['base_url'] . '/images/icon_sort_null';
                        $next_sort = 'asc';
                }		

                echo '<B><FONT size="-2"> '.$starting_index.'-'.$stoping_index.'/';
                echo $count; 
                echo(" found document(s)</FONT></B>\n");
                echo('<BR><BR>'."\n");
                $index = $starting_index;
                $url_pre = '<TD class=' . $css_td_class . 'NOWRAP><B><A HREF="' . $secureurl->encode($page_url . '&sort_order=' . $next_sort . '&sort_by=' . $sort_by) . '">';
                $url_post = '<B></A> <IMG SRC=' . $sort_img . '></TD>';
                $default_url_pre = "<TD class=$css_td_class NOWRAP><B><A HREF=\"";
                $link = "$page_url&sort_order=asc&sort_by=";
                $default_url_mid = '">';
                $default_url_post = "<B></TD>";
                echo("<TABLE name='list_file' border='0' hspace='0' hgap='0' CELLPADDING='1' CELLSPACING='1' >");
                echo("<TR bgcolor='83a9f7' id = '1'>");
                if($showCheckBox=='true')
                {
                        echo '<TD><input type="checkbox" onClick="selectAll(this)"></TD>';
                }
                if($sort_by == 'id')
                {
                        $str = $url_pre.'ID'.$url_post;
                }
                else
                {
                     $str = $default_url_pre . $secureurl->encode($link . 'id') . $default_url_mid.'ID'.$default_url_post;
                }
                echo($str);

                echo ('<th>View</th>');

                if($sort_by == 'file_name')
                {
                        $str = $url_pre.'File Name'.$url_post;
                }
                else
                { 
                        $str = $default_url_pre . $secureurl->encode($link .'file_name') . $default_url_mid.'File Name'.$default_url_post;
                }
                echo($str);

                if($sort_by == 'description')
                {
                        $str = $url_pre.'Descripton'.$url_post;
                }
                else
                {
                        $str = $default_url_pre. $secureurl->encode($link .'description') . $default_url_mid.'Description'.$default_url_post;
                }
                 echo($str);

                if($sort_by == 'access_right')
                {
                        $str = '<TD class="' . $css_td_class . '"><B>Rights<B><IMG SRC="' . $sort_img . '"></TD>';
                }
                else
                { 
                        $str = '<TD class="' . $css_td_class . '"><B>Rights<B></TD>';
                }
                echo($str);
                if($sort_by == 'created_date')
                {
                        $str = $url_pre.'Created Date'.$url_post;
                }
                else
                {
                        $str = $default_url_pre . $secureurl->encode($link .'created_date') . $default_url_mid.'Created Date'.$default_url_post;
                }
                echo($str);

                if($sort_by == 'modified_on')
                {
                        $str = $url_pre.'Modifed Date'.$url_post;
                }
                else
                {
                        $str = $default_url_pre . $secureurl->encode($link .'modified_on') . $default_url_mid.'Modified Date'.$default_url_post;
                }                
                echo($str);

                if($sort_by == 'author')
                {
                        $str = $url_pre.'Author'.$url_post;
                }
                else
                {
                        $str = $default_url_pre . $secureurl->encode($link .'author') . $default_url_mid.'Author'.$default_url_post;
                }
                echo($str);

                if($sort_by == 'department')
                {
                        $str = $url_pre.'Department'.$url_post;
                }
                else
                {
                        $str = $default_url_pre . $secureurl->encode($link . 'department') . $default_url_mid.'Department'.$default_url_post;
                }
                echo($str);
                
                $str = '<TD class="' . $css_td_class . '"><B>Size<B></TD>';
                echo($str);

                if($sort_by == 'status')
                {
                        $str = '<TD NOWRAP class="' . $css_td_class . '"><B>Avail<B> <IMG SRC="' . $sort_img . '"></TD>';
                }
                else
                {                
                        $str = '<TD NOWRAP class="' . $css_td_class . '"><B>Avail<B></TD>';
                }
                echo($str);		
                echo '</TR>';
                echo '<HD6>';
                $even_row_color = 'FCFCFC';
                $odd_row_color = 'E3E7F9';
                $unlock_highlighted_color = '#bdf9b6';
                $lock_highlighted_color = '#ea7741';
                echo "\n";
                if(!isset($fileid_array))
                {
                        echo '</TABLE>';
                        return 0;
                }
        		if(!isset($_REQUEST['state']))
        			$_REQUEST['state']=1;
                while($index<sizeof($fileid_array) and $index>=$starting_index and $index<=$stoping_index)
                {
                	if($index%2!=0)
                        {
                                $tr_bgcolor = $odd_row_color;
                        }
                        else
                        { 
                                $tr_bgcolor = $even_row_color;
                        }
                        $file_obj = new FileData($fileid_array[$index], $GLOBALS['connection'], $GLOBALS['database']);
						if ($file_obj->getStatus() == 0 and $userperms_obj->getAuthority($fileid_array[$index]) >= $userperms_obj->WRITE_RIGHT)
                        {
                                $lock = false;
                                $highlighted_color = $unlock_highlighted_color;
                        }
                        else
                        {
                                $lock = true;
                                $highlighted_color = $lock_highlighted_color;
                        }
                        
                        if($with_caption == true )
                        {
                                // correction for empty description
				echo '<TR bgcolor="' . $tr_bgcolor . '" id="' . $index . '" onMouseOver="this.style.backgroundColor=\'' . $highlighted_color . '\'" onMouseOut="this.style.backgroundColor=\'' . $tr_bgcolor . '\';">';
                        }
                        else
                        {
	                        echo '<TR bgcolor="' . $tr_bgcolor . '" id = "' . $index . '" onMouseOver="this.style.backgroundColor=\'' . $highlighted_color . '\';" onMouseOut="this.style.backgroundColor=\'' . $tr_bgcolor . '\';">';
                        } 
                        if ($file_obj->getDescription() == '') 
                        { 
                                $description = 'No description available';
                        }
                        // set filename for filesize() call below
                        //$filename = $dataDir . $file_obj->getId() . '.dat';
                        $fid = $file_obj->getId();


                        // begin displaying file list with basic information
                        $comment = $file_obj->getComment();
                        $description = $file_obj->getDescription();
                        $description = substr($description, 0, 35);
                        
                        
                        $created_date = fix_date($file_obj->getCreatedDate());
                        if ($file_obj->getModifiedDate())
                        {   
                        	$modified_date = fix_date($file_obj->getModifiedDate());
                        }
                        
                        //echo "$modified_date  and $fid fid";
                        
                        
                        $full_name_array = $file_obj->getOwnerFullName();
                        $owner_name = $full_name_array[1].', '.$full_name_array[0];
                        //$user_obj = new User($file_obj->getOwner(), $file_obj->connection, $file_obj->database);
                        $dept_name = $file_obj->getDeptName();
                        $realname = $file_obj->getRealname();
                        //$filesize = $file_obj->getFileSize();
                        //Get the file size in bytes.
                        $filesize = display_filesize($GLOBALS['CONFIG']['dataDir'] . $fileid_array[$index] . '.dat');

                        if($showCheckBox=='true')
                        {
				echo '<TD><input type="checkbox" value="' . $fid . '" name="checkbox' . $checkbox_index . '"></B></TD>';
			}
				echo '<TD class="' . $css_td_class . '">' . $fid . '<B></TD>';

                if ($userperms_obj->getAuthority($fileid_array[$index]) >= $userperms_obj->READ_RIGHT)
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

                    echo '<td class="' . $css_td_class . '" NOWRAP><a class="listtable" target="_blank" href="view_file.php?submit=view&id=' . urlencode($fid).'&mimetype='.urlencode("$lmimetype") . '"><img border=0 width="45" height="45" src="' . $GLOBALS['CONFIG']['base_url'] . '/images/view.png" title="View"alt="View"></a></td>';
                }
                else 
                {
                    echo "<td class=\"$css_td_class\" NOWRAP>&nbsp;</td>";
                }
                ?>


				<TD class="<?php $css_td_class;?>" NOWRAP><a class="listtable" href="<?php echo $secureurl->encode("details.php?id=$fid&state=" . ($_REQUEST['state']+1)) . "\">$realname</a></TD>"?>
<?php
                        	echo '<TD class="' . $css_td_class . '" NOWRAP>' . $description . '</TD>';							
                        $read = array($userperms_obj->READ_RIGHT, 'r');
                        $write = array($userperms_obj->WRITE_RIGHT, 'w');
                        $admin = array($userperms_obj->ADMIN_RIGHT, 'a');
                        $rights = array($read, $write, $admin);
                        $userright = $userperms_obj->getAuthority($file_obj->getId());
                        $index_found = -1;
                        //$rights[max][0] = admin, $rights[max-1][0]=write, ..., $right[min][0]=view
                        //if $userright matches with $rights[max][0], then this user has all the rights of $rights[max][0]
                        //and everything below it. 
                        for($i = sizeof($rights)-1; $i>=0; $i--)
                        {
                                if($userright==$rights[$i][0])
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
                        //For everything above it, blanck out
                        
                        for($i = $index_found+1; $i<sizeof($rights); $i++)
                        {
                                $rights[$i][1] = '-';
                        }
			            echo '<TD class="' . $css_td_class . '" NOWRAP>';

                        echo $rights[0][1];
                        for($i = 1; $i<sizeof($rights); $i++)
                        {
                                echo '|' . $rights[$i][1];
                        }
?>                      </TD>
                        <TD class="<?php echo $css_td_class; ?>" NOWRAP><?php echo $created_date;?></TD>
                        <TD class="<?php echo $css_td_class; ?>" NOWRAP><?php echo $modified_date;?></TD>
                        <TD class="<?php echo $css_td_class; ?>" NOWRAP><?php echo $owner_name; ?></TD>
                        <TD class="<?php echo $css_td_class; ?>" NOWRAP><?php echo $dept_name; ?></TD>
						<TD class="<?php echo $css_td_class; ?>" NOWRAP><?php echo $filesize; ?></TD> 	      <?php              
						if ($lock == false)
						{
							?><TD NOWRAP><CENTER><img src="<?php echo $GLOBALS['CONFIG']['base_url']; ?>/images/file_unlocked.png"></CENTER></TD><?php
						}
						else
						{
							?><TD align="center" NOWRAP><img src="<?php echo $GLOBALS['CONFIG']['base_url']; ?>/images/file_locked.png"></TD><?php
						}
                        
                        $index++;
                        ?></TR><?php
                        $checkbox_index++;
                }
                ?><INPUT type="hidden" name="num_checkboxes" value="<?php echo $checkbox_index;?>">
                </HD6>
                </TABLE>
                <Script Language="javascript">
                function selectAll(ctrl_checkbox)
                {
                        elements = document.forms[0].elements;
                        for(i = 0; i< elements.length; i++)
                                {
                                        if(elements[i].type == "checkbox")
                                                elements[i].checked = ctrl_checkbox.checked;
                                        }
                                } 
                </script>
                
                <!----------------------Table Ends----------------------->
<?php
                if (!isset($num_checkboxes))
                {
                        $num_checkboxes='0';
                }
                
                return $num_checkboxes;	
        }
        /**
         * list_nav_generator - Create pageination links
         * @return string
         * @param object $total_hit
         * @param object $page_limit
         * @param object $link_limit
         * @param object $page_url
         * @param object $current_page[optional]
         * @param object $sort_by[optional]
         * @param object $sort_order[optional]
         */

		function list_nav_generator($total_hit, $page_limit, $link_limit, $page_url, $current_page = 0, $sort_by = 'id', $sort_order = 'asc')
		{
			//enable secure URL

			//if the number of listing item is less than the configed number of item per page
			//no pagination needed
			if($total_hit<$page_limit)  return 0;
			echo '<center>Result Page:&nbsp;&nbsp;';

			//calculate number of pages for the number of hits on
			$num_pages = ceil($total_hit/($page_limit));

			//init
			$shown_pages = 0;
			$index_result = 0;

			// if there are more pages than the configed number of link allowed per page
			// show all upto $link_limit
			if($num_pages > $link_limit )   $shown_pages = $link_limit;

			// if the number is the same or less than, show all
			else { $shown_pages = $num_pages; }

			// suppose $current_page=2, $page_limit=15, then this will give a link to print
			// starting_index=15 and stopping_index=29.  That will be the Prev. link.
			// Page 0: 0-14, Page 1: 15-29, Page 2: 30-44
			if( $current_page > 0 )
			{
				echo '<a href="' . $page_url . '&sort_by=' . $sort_by . '&sort_order=' . $sort_order . '&starting_index=' . ($page_limit*($current_page-1)) . '&stoping_index=' . ($current_page*$page_limit-1) . '&page=' . ($current_page-1) . '">Prev</a>&nbsp; &nbsp;';
			}

			/* Suppose $link_limit is 20 and $current_page is 12.  Then $i=12 - 10=2.
			   See for loop below to see what $i is. */
			if($current_page >= $link_limit/2)
			{   $i = $current_page - $link_limit/2;     }
			/* Suppose $current_page is 8.  Then $i = 0*/
			else if($current_page < $link_limit/2)
			{   $i = 0; }

			// Suppose the admin define $link_limit = 20.  That means there are only 20 links available
			// on the navigator.  Ten of them is for moving backward and the other 10 is for moving forward
			// Suppose there are only 200 pages and $current_page is at 198.  Then the last page is the 200,
			// the max number of pages.
			if( $current_page + ceil($link_limit/2) > $num_pages)   $last_page = $num_pages;

			/* If not, the last page will be the current page + 10*/
			else    $last_page =  $current_page + ceil($link_limit/2);
			/*Suppose $i=2, $link_limit is 20, $current_page is 12, and $last_page=12+10=22
			  So why do I set $i?  Since $current_page=12, then the for loop will start at link 2 - 12 - 22,
			  where 12 is right in the middle.  Every time the user move forward, the window of 20 links,
			  10 on the left and 10 on the right, will move.*/
			for(; $i < $last_page; $i++)
			{
				/* There is no need to have the current page be a link.  The user only needs link
				   to move forward or backward. */
				//if($current_page== $i)  echo $i . '&nbsp;&nbsp;';
                $d = $i + 1;
                if($current_page== $i)  
                {
                    echo $d . '&nbsp;&nbsp;';
                }

				/* Generate link */
				else    
                    echo '<a href="' . $page_url . '&sort_by=' . $sort_by . '&sort_order=' . $sort_order . '&starting_index=' . ($i*$page_limit) . '&stoping_index=' . (($i+1)*$page_limit-1) . '&page=' . $i . '">' . $d . '</a>&nbsp;&nbsp;';
				$index_result = $index_result + $page_limit;
			}

			//Generate Next link
			if( $current_page < $num_pages-1 )
			{
				echo '<a href="' . $page_url . '&sort_by=' . $sort_by . '&sort_order=' . $sort_order . '&starting_index=' . ($page_limit*($current_page+1)) . '&stoping_index=' . (($current_page+2)*$page_limit-1) . '&page=' . ($current_page+1) . '">Next</a>&nbsp; &nbsp;';
			}
             echo '</center>';
		}

	function sort_browser()
	{
?>
		<SCRIPT language="javascript">
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
			<tr><td>Browse by:</td>
				<td NOWRAP ROWSPAN="0">
					<select name='category' onChange='loadItem(this)' width='0' size='1'>
						<option id='0' selected>Select one</option>
						<option id='1' value='author'>Author</option>
						<option id='2' value='department'>Department</option>
						<option id='3' value='category'>File Category</option>
<?php
	udf_functions_java_options(4);
?>
					</select>
				</td>
				<td>
					<select name='category_item' onChange='loadOrder(this)'>
						<option id='0' selected>Empty</option>
					</select>	
				</td>
				<td>
					<select name='category_item_order' onChange='load(this)'>
						<option id='0' selected>Empty</option>
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
	function checkUserPermission($file_id, $permittable_right)
	{
		$userperm_obj = new UserPermission($_SESSION['uid'], $GLOBALS['connection'], $GLOBALS['database']);
		if(!$userperm_obj->user_obj->isRoot() && $userperm_obj->getAuthority($file_id) < $permittable_right)
		{
			echo 'Error: OpenDocMan is unable to find the requested file.' . "\n";
			echo '       Please email <A href="mailto:' . $GLOBALS['CONFIG']['site_mail'] . '">Document Repository</A> for further assistance.';
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
		$lresult = mysql_query($lquery) or die('Error in querying: ' . $lquery . mysql_error());
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
            $unrx = '^[a-zA-Z0-9]'; // allow only letters and numbers. Limit 5 - 25 characters.
            if(ereg($unrx, $username))
                return true;
            else
                return false;
        }
}

function cleanInput($input) {
 
$search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
);
 
    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitize($input) {
    if (is_array($input)) 
    {
        foreach($input as $var=>$val) 
        {
            $output[$var] = sanitize($val);
        }
    } 
    else 
    {
        if (get_magic_quotes_gpc()) 
        {
            $input = stripslashes($input);
        }
        $input  = cleanInput($input);
        $output = mysql_real_escape_string($input);
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

?>
