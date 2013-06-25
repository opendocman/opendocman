<?php
/*
ajax_udf.php 
Copyright (C) 2012 Stephen Lawrence Jr.

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

include('odm-load.php');

$q=$_GET["q"];
?>
    <table border="0">
        <tr>
<?php
    if($q != "" && $_GET["add_value"] != "add" && $_GET["add_value"] != "edit"){
?>
            <td>
<?php 
                    $explode_add_value = explode('_', $_GET["add_value"]);
                    $field_name = $explode_add_value[2];

                    $query = 'SELECT * FROM ' . $_GET["add_value"];
                    $result = mysql_query($query);

                    if ($result && $q != 'primary') {
                        echo '<table>';
                        echo '<tr><th style="padding-left:39px;">' . msg('label_primary_type') . ':</th><td><select name=primary_type class="required" onchange="showdivs(this.value,\'' . $_GET["add_value"] . '\')">';
                        echo '<option value="0">Please select one</option>';
                        while ($row = mysql_fetch_row($result)) {
                            echo '<option value=' . $row[0] . ' ' . ($row[0] == $q ? "selected" : "") . '>' . $row[1] . '</option>'; //CHM
                        }
                        echo '</select></td></tr>';
                        echo '</table>';
                    }
                  
                    if ($q == 'secondary') {
                        $tablename = '_secondary';
                    } elseif ($q == 'primary') {
                        $tablename = '_primary';
                    } else {
                        $tablename = '_secondary WHERE pr_id = "' . $_GET['q'] . '"';
                    }

                    echo '<table>';
                    echo '<tr bgcolor="83a9f7">
                            <th>' . msg('button_delete') . '?</th>
                            <th>' . msg('value') . '</th>
                          </tr>';

                    if ( ( ( (int) $q == $q && (int) $q > 0 ) || $q == 'primary' ) ) {
                        $query = 'SELECT * FROM ' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $field_name . $tablename;
                       
                        $result = mysql_query($query);
                        while ($row = mysql_fetch_row($result)) {
                            if (isset($bg) && $bg == "FCFCFC") {
                                $bg = "E3E7F9";
                            } else {
                                $bg = "FCFCFC";
                            }
                            echo '<tr bgcolor="' . $bg . '">
                                    <td align=center><input type=checkbox name=x' . $row[0] . '></td>
                                        <td>' . $row[1] . '</td>
                                  </tr>';
                        }
                        mysql_free_result($result);
                    }

                    echo '<tr>
                            <th align=right>' . msg('new') . ':</th>
                            <td><input type=textbox maxlength="16" name="newvalue"></td>
                          </tr>';
                    echo '<tr><td colspan="2">';
                    echo '<div class="buttons">
                            <button class="positive" type="submit" value="Update">' . msg('button_update') . '</button>';
?>
                            <button class="negative" type="Submit" name="cancel" value="Cancel"><?php echo msg('button_cancel')?></button>
                          </div>
                        </td>
                        </tr>
                        </table>
<?php 
    draw_footer();
}

if ($_GET["add_value"] == "add") {
    $query = 'Select * FROM ' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary WHERE pr_id = "' . $_GET['q'] . '"';
    $subresult = mysql_query($query);

    echo '<select id="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary" name="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary">';
    while ($subrow = mysql_fetch_row($subresult)) {
        echo '<option value="' . $subrow[0] . '">' . $subrow[1] . '</option>';
    }
    echo '</select>';
}

if ($_GET["add_value"] == "edit") {
    $query = 'Select * FROM ' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary WHERE pr_id = "' . $_GET['q'] . '"';
    $subresult = mysql_query($query);

    echo '<select id="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary" name="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_GET['table'] . '_secondary">';
    while ($subrow = mysql_fetch_row($subresult)) {
        echo '<option value="' . $subrow[0] . '">' . $subrow[1] . '</option>';
    }
    echo '</select>';
}
?>
                    
    </tr>
</table>