<?php
/*
add.php - adds files to the repository
Copyright (C) 2002-2007 Stephen Lawrence Jr., Jon Miner
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


// You can add signup_header.html and signup_footer.html files to display on this page automatically

include('odm-load.php');
if($GLOBALS['CONFIG']['allow_signup'] == 'True')
{

    // Submitted so insert data now
    if(isset($_REQUEST['adduser']))
    {
        // Check to make sure user does not already exist
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE username = '" . addslashes($_POST['username']) . '\'';
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // If the above statement returns more than 0 rows, the user exists, so display error
        if(mysql_num_rows($result) > 0)
        {
            echo msg('message_user_exists');
            exit;
        }
        else
        {
            $phonenumber = (!empty($_REQUEST['phonenumber']) ? $_REQUEST['phonenumber'] : '');
            // INSERT into user
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}user (username, password, department, phone, Email,last_name, first_name) VALUES('". addslashes($_POST['username'])."', md5('". addslashes(@$_REQUEST['password']) ."'), '" . addslashes($_REQUEST['department'])."' ,'" . addslashes($phonenumber) . "','". addslashes($_REQUEST['Email'])."', '" . addslashes($_REQUEST['last_name']) . "', '" . addslashes($_REQUEST['first_name']) . '\' )';
            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
            // INSERT into admin
            $userid = mysql_insert_id($GLOBALS['connection']);
            if (!isset($_REQUEST['admin']))
            {
                $_REQUEST['admin']='';
            }
            $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}admin (id, admin) VALUES('$userid', '$_REQUEST[admin]')";
            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
            if(isset($_REQUEST['reviewer']))
            {
                for($i = 0; $i<sizeof($_REQUEST['department_review']); $i++)
                {
                    $dept_rev=$_REQUEST['department_review'][$i];
                    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id, user_id) values('$dept_rev', $userid)";
                    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
                }
            }

            // mail user telling him/her that his/her account has been created.
            echo msg ('message_account_created') . ' ' . $_POST['username'].'<br />';
            if($GLOBALS['CONFIG']['authen'] == 'mysql')
            {
                echo msg('message_account_created_password') . ': '.$_REQUEST['password']."\n\n";
                echo '<br><a href="' . $GLOBALS['CONFIG']['base_url'] . '">' . msg('login'). '</a>';
                exit;
            }
        }
    }
    elseif(isset($_REQUEST['updateuser']))
    {
        if(!isset($_REQUEST['admin']) || $_REQUEST['admin'] == '')
        {
            $_REQUEST['admin'] = 'no';
        }

        if(!isset($_REQUEST['caller']) || $_REQUEST['caller'] == '')
        {
            $_REQUEST['caller'] = 'admin.php';
        }

        $user_obj = new User($_REQUEST['id'], $GLOBALS['connection'], DB_NAME);

        // UPDATE admin info
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}admin set admin='". $_REQUEST['admin'] . "' where id = '".$_REQUEST['id']."'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        // UPDATE into user
        $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET username='". addslashes($_POST['username']) ."',";
        if (!empty($_REQUEST['password']))
        {
            $query .= "password = md5('". addslashes($_REQUEST['password']) ."'), ";
        }
        if( isset( $_REQUEST['department'] ) )
        {
            $query.= 'department="' . addslashes($_REQUEST['department']) . '",';
        }
        if( isset( $_REQUEST['phonenumber'] ) )
        {
            $query.= 'phone="' . addslashes($_REQUEST['phonenumber']) . '",';
        }
        if( isset( $_REQUEST['Email'] ) )
        {
            $query.= 'Email="' . addslashes($_REQUEST['Email']) . '" ,';
        }
        if( isset( $_REQUEST['last_name'] ) )
        {
            $query.= 'last_name="' . addslashes($_REQUEST['last_name']) . '",';
        }
        if( isset( $_REQUEST['first_name'] ) )
        {
            $query.= 'first_name="' . addslashes($_REQUEST['first_name']) . '" ';
        }
        $query.= 'WHERE id="' . $_REQUEST['id'] . '"';
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

        // UPDATE into dept_reviewer
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = '$_REQUEST[id]'";
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
        if(isset($_REQUEST['reviewer']))
        {
            //Remove all entry for $id
            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer where user_id = $_REQUEST[id]";
            $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
            $depts_rev = $_REQUEST['department_review'];
            for($i = 0; $i<sizeof($_REQUEST['department_review']); $i++)
            {
                $dept_rev=$depts_rev[$i];
                $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer (dept_id, user_id) values('$dept_rev', $_REQUEST[id])";
                $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query". mysql_error());
            }
        }
        // back to main page
        if(!isset($_REQUEST['caller']))
        {
            $_REQUEST['caller'] = 'admin.php';
        }
        $_REQUEST['last_message'] = urlencode('User successfully updated');
        header('Location: ' . $_REQUEST['caller'] . '?last_message=' . $_REQUEST['last_message']);
    }
    ?>
        <html>
        <head><title>Sign Up</title></head>
        <body>
<?php
    if (is_readable("signup_header.html"))
    {
      include("signup_header.html");
    }
?>
                
            <font size=6>Sign Up</font>
        <br><script type="text/javascript" src="FormCheck.js"></script>


        <table border="0" cellspacing="5" cellpadding="5">
        <form name="add_user" action="signup.php" method="POST" enctype="multipart/form-data">
        <tr><td><b><?php echo msg('label_last_name');?></b></td><td><input name="last_name" type="text"></td></tr>
        <tr><td><b><?php echo msg('label_first_name');?></b></td><td><input name="first_name" type="text"></td></tr>
        <tr><td><b><?php echo msg('username');?></b></td><td><input name="username" type="text"></td></tr>
        <tr>
        <td><b>Phone Number</b></td>
        <td>
        <input name="phonenumber" type="text">
        </td>
        </tr>
        <tr>
        <td><b>Example</b></td>
        <td><b>999 9999999</b></td>
        </tr>
        <tr>
        <td><b>E-mail Address</b></td>
        <td>
        <input name="Email" type="text">
        </td>
        </tr>
        <tr>
        <?php
        // If mysqlauthentication, then ask for password
        if( $GLOBALS['CONFIG']['authen'] =='mysql')
        {
            $rand_password = makeRandomPassword(); 
            echo '<INPUT type="hidden" name="password" value="' . $rand_password . '">';
        }
    ?>

        <tr>
        <td><b>Department</b></td>
        <td>
        <select name="department">
        <?php			
        // query to get a list of departments
        $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

    while(list($id, $name) = mysql_fetch_row($result))
    {
        echo '<option value=' . $id . '>' . $name . '</option>';
    }

    mysql_free_result ($result);
    ?>
        </select>
        </td>
        <tr>
        <td></td>
        <td columnspan=3 align="center"><input type="Submit" name="adduser" onClick="return validatemod(add_user);" value="<?php echo msg('submit');?>">
        </form>
        </td>
        </tr>
        </table>
<?php
   if (is_readable("signup_footer.html"))
   {
       include("signup_footer.html");
   }
?>

        </body>
        </html>
        <?php
}