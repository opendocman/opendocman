<?php
/*
udf_functions.php - adds user definced functions
Copyright (C) 2007  Stephen Lawrence Jr., Jonathan Miner
Copyright (C) 2008-2011 Stephen Lawrence Jr.
 
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

// User Defined Fields START

if ( !defined('udf_functions') )
{
    define('udf_functions', 'true', false);

    function udf_add_file_form()
    {
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER by id";
        $result = mysql_query($query) or die ("Error in query32: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            echo '<tr><td>';
            if (file_exists("udf_help.html"))
            {
                echo '<a class="body" href="udf_help.html#Add_File_'.$row[2].'" onClick="return popup(this,\'Help\')" style="text-decoration:none">'.$row[2].'</a>';
            }
            else
            {
                echo $row[2];
            }

            echo '</td><td>';

            //Type is Select List
            if ( $row[1] == 1 )
            {
                echo '<select name="'.$row[0].'">';
                $query = "SELECT id,value FROM ".$row[0];
                $subresult = mysql_query($query) or die ("Error in query52: $query. " . mysql_error());
                while ($subrow = mysql_fetch_row($subresult))
                {
                    echo '<option value="'.$subrow[0].'">'.$subrow[1].'</option>';
                }
                mysql_free_result($subresult);
                echo '</select>';
            }

            // Type is Radio
            if ( $row[1] == 2 )
            {
                $query = "SELECT id,value FROM ".$row[0];
                $subresult = mysql_query($query) or die ("Error in query65: $query. " . mysql_error());
                while ($subrow = mysql_fetch_row($subresult))
                {
                    echo '<input type=radio name="'.$row[0].'" value="'.$subrow[0].'">'.$subrow[1];
                }
                mysql_free_result($subresult);
            }

            // Type is Text
            if ( $row[1] == 3 )
            {
                echo '<input tabindex="5" type="Text" name="'.$row[0].'" size="16">';
            }
            echo '</td></tr>';
        }
        mysql_free_result($result);
    }

    function udf_add_file_insert($fileId)
    {
        $query = "SELECT table_name,field_type FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query86: $query . " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2 || $row[1] == 3)
            {
                if (isset($_REQUEST[$row[0]]) && $_REQUEST[$row[0]] != "" )
                {
                    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET `{$row['0']}` = '{$_REQUEST[$row['0']]}' WHERE id = '$fileId'";
                    mysql_query($query) or die ("Error in query94: $query. " . mysql_error());
                }
            }
        }
        mysql_free_result($result);
    }

    function udf_edit_file_form()
    {
        $query = "SELECT display_name,field_type,table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query104: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2)
            {
                echo '<tr><td>' . $row[0] . '</td><td>';
                if ( $row[1] == 1 )
                {
                    echo '<select name="'.$row[2].'">';
                }

                $query = "SELECT {$row['2']} FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = '{$_REQUEST['id']}'";
                $subresult = mysql_query($query) or die ("Error in query116: $query. " . mysql_error());
                $subrow = mysql_fetch_row($subresult);
                $sel = $subrow[0];
                mysql_free_result($subresult);

                $query = 'SELECT id, value FROM ' . $row[2];
                $subresult = mysql_query($query) or die ("Error in query122: $query. " . mysql_error());
                while ($subrow = mysql_fetch_row($subresult))
                {
                    if ( $row[1] == 1 )
                    {
                        echo '<option value="' . $subrow[0] . '"';
                        if ( $sel == $subrow[0] )
                        {
                            echo ' selected';
                        }
                        echo '>' . $subrow[1] . '</option>';
                    }
                     elseif ($row[1] == 2)
                    {
                        echo '<input type=radio name="' . $row[2] . '" value="' . $subrow[0] . '"';
                        if ($sel == $subrow[0])
                            echo ' checked';
                        echo '>' . $subrow[1];
                    }
                }
                mysql_free_result($subresult);
                if ($row[1] == 1)
                    echo '</select>';
                echo '</td></tr>';
            }
            elseif ($row[1] == 3)
            {
                echo '<tr><td>' . $row[0] . '</td><td>';
                $query = "SELECT {$row['2']} FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE id = '{$_REQUEST['id']}'";
                $subresult = mysql_query($query) or die ("Error in query151: $query. " . mysql_error());
                $subrow = mysql_fetch_row($subresult);
                echo '<input type="text" name="' . $row[2] . '" size="50" value="' . $subrow[0] . '">';
                mysql_free_result($subresult);
            }
        }
        mysql_free_result($result);
    }

    function udf_edit_file_update()
    {
        $query = "SELECT display_name,field_type,table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query163: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2 || $row[1] == 3)
            {
                if ( isset($_REQUEST[$row[2]]) && $_REQUEST[$row[2]] != "" )
                {
                    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET `{$row['2']}`='{$_REQUEST[$row['2']]}' WHERE id = {$_REQUEST['id']}";
                    $subresult = mysql_query($query) or die ("Error in query171: $query. " . mysql_error());
                }
            }
        }
        mysql_free_result($result);
    }

    function udf_details_display($fileId)
    {
        $query = "SELECT display_name,field_type,table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query181: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2)
            {
                $query = "SELECT value FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$row['2']} WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = $fileId AND {$GLOBALS['CONFIG']['db_prefix']}data.{$row['2']}={$row['2']}.id";
                $subresult = mysql_query($query) or die ("Error in query187: $query. " . mysql_error());
                if($subresult)
                {
                    $subrow = mysql_fetch_row($subresult);
                    echo '<th valign=top align=right>' . $row[0] . ':</th><td>' . $subrow[0] . '</td></tr>';
                    mysql_free_result($subresult);
                }
            } 
            elseif ($row[1] == 3)
            {
                $query = "SELECT {$row[2]} FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = $fileId ";
                $subresult = mysql_query($query) or die ("Error in query198: $query. " . mysql_error());
                if ($subresult)
                {
                    $subrow = mysql_fetch_row($subresult);
                    echo '<th valign=top align=right>' . $row[0] . ':</th><td>' . $subrow[0] . '</td></tr>';
                    mysql_free_result($subresult);
                }

            }
        }
        mysql_free_result($result);
    }

    function udf_admin_header()
    {
        echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' .msg('label_user_defined_fields'). '</font></th>';
    }

    function udf_admin_menu($secureurl)
    {
        echo '<td valign=top><table border=0>';
        echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=add&state=' . ($_REQUEST['state']+1)).'">' .msg('label_add'). '</a></b></td></tr>';
        echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=deletepick&state=' . ($_REQUEST['state']+1)).'">' .msg('label_delete'). '</a></b></td></tr>';
        echo '<tr><td><hr></td></tr>';
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query223: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=edit&udf='.$row[0].'&state=' . ($_REQUEST['state']+1)).'">'.$row[2].'</a></b></td></tr>';
        }
        mysql_free_result($result);
        echo '</table></td>';
    }

    function udf_functions_java_menu()
    {
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf order by id";
        $result = mysql_query($query) or die ("Error in query235: $query. " . mysql_error());
        while ( $row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2 || $row[1] == 3 )
            {
                echo "case '".$row[2]."':\n";
                echo "      info_Array = ".$row[0]."_array;\n";
                echo "      break;\n";
            }
        }
        mysql_free_result($result);
    }

    function udf_functions_java_array()
    {
        $query = "SELECT table_name,field_type FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query251: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {           
            if ($row[1] == 1 || $row[1] == 2)
            {
                $query = "SELECT id,value FROM " . $row[0];
          
                $subresult = mysql_query($query) or die("Error in query255: $query. " . mysql_error());
                echo $row[0] . "_array = new Array();\n";              
                $index = 0;
                while ($subrow = mysql_fetch_row($subresult))
                {
                    echo "\t" . $row[0] . "_array[" . $index . "] = new Array(\"" . $subrow[1] . "\", " . $subrow[0] . ");\n";
                    $index++;
                }
            }
        }
        mysql_free_result($result);
    }

    function udf_functions_java_options($id)
    {
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf order by id";
        $result = mysql_query($query) or die ("Error in query270: $query. " . mysql_error());
        while ( $row = mysql_fetch_row($result))
        {
            if ( $row[1] == 1 || $row[1] == 2)
            {
                echo '<option id="'.$id.'" value="'.$row[2].'">'.$row[2].'</option>';
                $id++;
            }
        }
        mysql_free_result($result);
    }

    function udf_functions_add_udf()
    {
        if(empty($_REQUEST['table_name']))
        {
            $secureurl = new phpsecureurl;
            header('Location: ' . $secureurl->encode('admin.php?last_message=' . msg('message_udf_cannot_be_blank') ));
            exit;
        }

        if(empty($_REQUEST['display_name']))
        {
            $secureurl = new phpsecureurl;
            header('Location: ' . $secureurl->encode('admin.php?last_message=' . msg('message_udf_cannot_be_blank') ));
            exit;
        }
        
        $table_name = str_replace(' ', '', $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_REQUEST['table_name']);

        if(!preg_match('/^\w+$/',$table_name))
        {
            $secureurl = new phpsecureurl;
            header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Invalid+Name+(A-Z 0-9 Only)'));
            exit;
        }

        // Check for duplicate table name
        $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE table_name='$table_name'";
        $result = mysql_query($query);
        //echo mysql_num_rows($result);
        if (mysql_numrows($result) == "0")
        {
            if ($_REQUEST['field_type'] == 1 || $_REQUEST['field_type'] == 2)
            {               
                // They have chosen Select list of Radio list
                // 
                // First we add a new column in the data table
                $query = 'ALTER TABLE ' . $GLOBALS['CONFIG']['db_prefix'] . 'data ADD COLUMN ' . $table_name . ' int AFTER category';
                $result = mysql_query($query);
                if (!$result)
                {
                    $secureurl = new phpsecureurl;
                    header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Problem+With+Alter'));
                    exit;
                }

                // Now we need to create a new table to store the UDF Info
                $query = 'CREATE TABLE ' . $table_name . ' ( id int auto_increment unique, value varchar(64) )';
                $result = mysql_query($query);
                if (!$result)
                {
                    // If the CREATE fails, rollback the ALTER
                    $query = 'ALTER TABLE ' . $GLOBALS['CONFIG']['db_prefix'] . 'data DROP COLUMN ' . $table_name;
                    $result = mysql_query($query);
                    
                    $secureurl = new phpsecureurl;
                    header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Problem+With+Create'));
                    exit;
                }

                // And finally, add an entry into the udf table
                $query = 'INSERT into ' . $GLOBALS['CONFIG']['db_prefix'] . 'udf (table_name,display_name,field_type) VALUES ("' . $table_name . '","' . $_REQUEST['display_name'] . '",' . $_REQUEST['field_type'] . ')';
                $result = mysql_query($query);
                if (!$result)
                {
                    // If the INSERT fails, rollback the CREATE and ALTER
                    $query = 'DROP TABLE ' . $table_name;
                    $result = mysql_query($query);

                    $query = 'ALTER TABLE ' . $GLOBALS['CONFIG']['db_prefix'] . 'data DROP COLUMN ' . $table_name;
                    $result = mysql_query($query);

                    $secureurl = new phpsecureurl;
                    header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Problem+With+INSERT'));
                    exit;
                }
            } 
            elseif ($_REQUEST['field_type'] == 3)
            {             
                // The have chosen a text field
                $query = 'ALTER TABLE ' . $GLOBALS['CONFIG']['db_prefix'] . 'data ADD COLUMN ' . $table_name . ' varchar(255) AFTER category';
                $result = mysql_query($query);
                if (!$result)
                {
                    $secureurl = new phpsecureurl;
                    header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Problem+With+Alter'));
                    exit;
                }

                $query = 'INSERT into ' . $GLOBALS['CONFIG']['db_prefix'] . 'udf (table_name,display_name,field_type) VALUES ("' . $table_name . '","' . $_REQUEST['display_name'] . '",' . $_REQUEST['field_type'] . ')';
                $result = mysql_query($query);
                if (!$result)
                {                    
                    // If the INSERT fails, rollback the ALTER
                    $query = 'ALTER TABLE ' . $GLOBALS['CONFIG']['db_prefix'] . 'data DROP COLUMN ' . $table_name;
                    $result = mysql_query($query);
                    
                    $secureurl = new phpsecureurl;
                    header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Problem+With+INSERT'));
                    exit;
                }
            }
        } 
        else
        {
            $secureurl = new phpsecureurl;
            header('Location: ' . $secureurl->encode('admin.php?last_message=Error+:+Duplicate+Table+Name'));
            exit;
        }
    }

    function udf_functions_delete_udf()
    {       
        $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}udf where table_name='{$_REQUEST['id']}'";
        mysql_query($query) or die ("Error in query343: $query. " . mysql_error());

        $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$_REQUEST['id']}";
        $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
        
        $query = 'DROP TABLE IF EXISTS ' . $_REQUEST['id'];
        mysql_query($query) or die("Error in query346: $query. " . mysql_error());          
    }

    function udf_functions_search_options()
    {
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $result = mysql_query($query) or die ("Error in query355: $query. " . mysql_error());
        while ($row = mysql_fetch_row($result))
        {
            echo '<option value="'.$row[2].'_only">'.$row[2].' only</option>';
        }
        mysql_free_result($result);
    }

    function udf_functions_search($lwhere,$lquery_pre,$lquery,$lequate,$lkeyword)
    {
        $tmp = $lwhere;
        $dn = strtok($tmp,"_");

        $query = "SELECT table_name,field_type FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE display_name = \"" . $dn . "\"";
        $result = mysql_query($query) or die ("Error in query369: $query. " . mysql_error());
        $row = mysql_fetch_row($result);
        if ($row[1] == 1 || $row[1] == 2)
        {
            $lquery_pre .= ', ' . $row[0];
            $lquery .= $row[0] . '.value' . $lequate . '\'' . $lkeyword . '\'';
            $lquery .= ' AND ' . $GLOBALS['CONFIG']['db_prefix'] . 'data.' . $row[0] . ' = ' . $row[0] . '.id';
        }
        elseif ($row[1] == 3)
        {
            $lquery .= $row[0] . $lequate . '\'' . $lkeyword . '\'';
        }
        mysql_free_result($result) or die ("Error in query381: $query. " . mysql_error());

        return array($lquery_pre,$lquery);
    }
// User Defined Fields END
}