<?php
/*
udf_functions.php - adds user definced functions
Copyright (C) 2007  Stephen Lawrence, Jonathan Miner

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

// User Defined Fields START

if ( !defined('udf_functions') )
{
  define('udf_functions', 'true', false);

function udf_add_file_form()
{
  $query = "SELECT table_name,field_type,display_name FROM udf ORDER by id";
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    echo '<tr><td>';
    if (file_exists("udf_help.html"))
      echo '<a class="body" href="udf_help.html#Add_File_'.$row[2].'" onClick="return popup(this,\'Help\')" style="text-decoration:none">'.$row[2].'</a>';
    else
      echo $row[2];

    echo '</td><td>';
    if ( $row[1] == 1 ) {
      echo '<select name="'.$row[0].'">';
      $query = "SELECT id,value FROM ".$row[0];
      $subresult = mysql_query($query);
      while ($subrow = mysql_fetch_row($subresult)) {
        echo '<option value="'.$subrow[0].'">'.$subrow[1].'</option>';
      }
      mysql_free_result($subresult);
      echo '</select>';
    }
    if ( $row[1] == 2 ) {
      $query = "SELECT id,value FROM ".$row[0];
      $subresult = mysql_query($query);
      while ($subrow = mysql_fetch_row($subresult)) {
        echo '<input type=radio name="'.$row[0].'" value="'.$subrow[0].'">'.$subrow[1];
      }
      mysql_free_result($subresult);
    }
    echo '</td></tr>';
  }
  mysql_free_result($result);
}

function udf_add_file_insert($fileId)
{
  $query = "SELECT table_name,field_type FROM udf ORDER BY id";
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 || $row[1] == 2) {
      if ( $_REQUEST[$row[0]] != "" ) {
        $query = 'UPDATE data SET '.$row[0].' = '.$_REQUEST[$row[0]].' WHERE id = '.$fileId;
        mysql_query($query);
      }
    }
  }
  mysql_free_result($result);
}

function udf_edit_file_form()
{
  $query = 'SELECT display_name,field_type,table_name FROM udf ORDER BY id';
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 || $row[1] == 2) {
      echo '<tr><td>' . $row[0] . '</td><td>';
      if ( $row[1] == 1 )
        echo '<select name="'.$row[2].'">';

      $query = 'SELECT ' . $row[2] . ' FROM data WHERE id = '.$_REQUEST['id'];
      $subresult = mysql_query($query);
      $subrow = mysql_fetch_row($subresult);
      $sel = $subrow[0];
      mysql_free_result($subresult);

      $query = 'SELECT id, value FROM ' . $row[2];
      $subresult = mysql_query($query);
      while ($subrow = mysql_fetch_row($subresult)) {
        if ( $row[1] == 1 ) {
          echo '<option value="' . $subrow[0] . '"';
          if ( $sel == $subrow[0] )
            echo ' selected';
          echo '>' . $subrow[1] . '</option>';
        }
        else {
          echo '<input type=radio name="'.$row[2].'" value="'.$subrow[0].'"';
          if ( $sel == $subrow[0] )
            echo ' checked';
          echo '>'.$subrow[1];
        }
      }
      mysql_free_result($subresult);
      if ( $row[1] == 1 )
        echo '</select>';
      echo '</td></tr>';
    }
  }
  mysql_free_result($result);
}

function udf_edit_file_update()
{
  $query = 'SELECT display_name,field_type,table_name FROM udf ORDER BY id';
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 || $row[1] == 2) {
      if ( $_REQUEST[$row[2]] != "" ) {
        $query = 'UPDATE data SET '.$row[2].'="'.$_REQUEST[$row[2]].'" WHERE id = '.$_REQUEST['id'];
        $subresult = mysql_query($query);
      }
    }
  }
  mysql_free_result($result);
}

function udf_details_display($fileId)
{
  $query = 'SELECT display_name,field_type,table_name FROM udf ORDER BY id';
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 || $row[1] == 2) {
      $query = 'SELECT value FROM data, ' . $row[2] . ' WHERE data.id = ' . $fileId . ' AND data.' . $row[2] . ' = ' . $row[2] . '.id';
      $subresult = mysql_query($query);
      if($subresult)
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
	echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">User Defined<br>Fields</font></th>';
}

function udf_admin_menu($secureurl)
{
  echo '<td valign=top><table border=0>';
  echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=add&state=' . ($_REQUEST['state']+1)).'">Add</a></b></td></tr>';
  echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=deletepick&state=' . ($_REQUEST['state']+1)).'">Delete</a></b></td></tr>';
  echo '<tr><td><hr></td></tr>';
  $query = 'SELECT table_name,field_type,display_name FROM udf ORDER BY id';
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    echo '<tr><td><b><a href="'.$secureurl->encode('udf.php?submit=edit&udf='.$row[0].'&state=' . ($_REQUEST['state']+1)).'">'.$row[2].'</a></b></td></tr>';
  }
  mysql_free_result($result);
  echo '</table></td>';
}

function udf_functions_java_menu()
{
  $query = 'SELECT table_name,field_type,display_name FROM udf order by id';
  $result = mysql_query($query);
  while ( $row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 || $row[1] == 2 ) {
      echo "case '".$row[2]."':\n";
      echo "      info_Array = ".$row[0]."_array;\n";
      echo "      break;\n";
    }
  }
  mysql_free_result($result);
}

function udf_functions_java_array()
{
  $query = "SELECT table_name,field_type from udf ORDER BY id";
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    $query = "SELECT id,value FROM ".$row[0];
    $subresult = mysql_query($query);
    echo $row[0]."_array = new Array();\n";
    $index = 0;
    while ($subrow = mysql_fetch_row($subresult)) {
      echo "\t".$row[0]."_array[".$index."] = new Array(\"".$subrow[1]."\", ".$subrow[0].");\n";
      $index++;
    }
    mysql_free_result($subresult);
  }
}

function udf_functions_java_options($id)
{
  $query = 'SELECT table_name,field_type,display_name FROM udf order by id';
  $result = mysql_query($query);
  while ( $row = mysql_fetch_row($result)) {
    if ( $row[1] == 1 ) {
      echo '<option id="'.$id.'" value="'.$row[2].'">'.$row[2].'</option>';
      $id++;
    }
  }
  mysql_free_result($result);
}

function udf_functions_add_udf()
{
    $table_name = str_replace(' ', '', $_REQUEST['table_name']);

  if ( $_REQUEST['field_type'] == 1 || $_REQUEST['field_type'] == 2) {
    $query = 'INSERT into udf (table_name,display_name,field_type) VALUES ("' . $table_name . '","'.$_REQUEST['display_name'].'",'.$_REQUEST['field_type'].')';
    mysql_query($query);
    $query = 'ALTER TABLE data ADD COLUMN '.$table_name.' int AFTER category';
    mysql_query($query);
    $query = 'CREATE TABLE ' . $table_name . ' ( id int auto_increment unique, value varchar(16) )';
    mysql_query($query);
  }

}

function udf_functions_delete_udf()
{
  $query = 'DELETE from udf where table_name="'.$_REQUEST['id'].'"';
  mysql_query($query);

  $query = 'DROP TABLE '.$_REQUEST['id'];
  mysql_query($query);

  $query = 'ALTER TABLE data DROP COLUMN '.$_REQUEST['id'];
  mysql_query($query);
}

function udf_functions_search_options()
{
  $query = 'SELECT table_name,field_type,display_name FROM udf ORDER BY id';
  $result = mysql_query($query);
  while ($row = mysql_fetch_row($result)) {
    echo '<option value="'.$row[2].'_only">'.$row[2].' only</option>';
  }
  mysql_free_result($result);
}

function udf_functions_search($lwhere,$lquery_pre,$lquery,$lequate,$lkeyword)
{
  $tmp = $lwhere;
  $dn = strtok($tmp,"_");

  $query = "SELECT table_name FROM udf WHERE display_name = \"" . $dn . "\"";
  $result = mysql_query($query);
  $row = mysql_fetch_row($result);

  $lquery_pre .= ', '.$row[0];
  $lquery .= $row[0].'.value' . $lequate  . '\'' . $lkeyword . '\'';
  $lquery .= ' AND data.'.$row[0].' = '.$row[0].'.id';
  mysql_free_result($result);

  return array($lquery_pre,$lquery);
}
// User Defined Fields END
}
?>
