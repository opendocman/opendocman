<?php
/* 
Copyright (C) 2014 Stephen Lawrence Jr.

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

if(isset($_GET['q'])) {
    $q=preg_replace('/ /', '', $_GET['q']);
}

if(isset($_GET['add_value'])) {
    $add_value = preg_replace('/ /', '', $_GET['add_value']);
}

if(isset($_GET['table'])) {
    $tablename = preg_replace('/ /', '', $_GET['table']);
}
?>
    <table border="0">
        <tr>
<?php
if($q != "" && $add_value != "add" && $add_value != "edit"){
?>
            <td>
<?php
    $explode_add_value = explode('_', $add_value);
    if (isset($explode_add_value[2])) {
        $field_name = $explode_add_value[2];
    } else {
        $field_name = '';
    }
    if ($add_value != '' && $field_name != '') {
        $query = 'SELECT * FROM ' . $add_value;
        $result = mysql_query($query);

        if ($result && $q != 'primary') {
            echo '<table>';
            echo '<tr><th style="padding-left:39px;">' . msg('label_primary_type') . ':</th><td><select name=primary_type class="required" onchange="showdivs(this.value,\'' . $add_value . '\')">';
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
            $tablename = '_secondary WHERE pr_id = "' . $q . '"';
        }

        echo '<table>';
        echo '<tr bgcolor="83a9f7">
                            <th>' . msg('button_delete') . '?</th>
                            <th>' . msg('value') . '</th>
                          </tr>';

        if (( ( (int) $q == $q && (int) $q > 0 ) || $q == 'primary')) {
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
}

if ($add_value == "add") {
    $query = 'Select * FROM ' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary WHERE pr_id = "' . $q . '"';
    $subresult = mysql_query($query);

    echo '<select id="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary" name="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary">';
    while ($subrow = mysql_fetch_row($subresult)) {
        echo '<option value="' . $subrow[0] . '">' . $subrow[1] . '</option>';
    }
    echo '</select>';
}

if ($add_value == "edit") {
    $query = 'Select * FROM ' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary WHERE pr_id = "' . $q . '"';
    $subresult = mysql_query($query);

    echo '<select id="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary" name="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $tablename . '_secondary">';
    while ($subrow = mysql_fetch_row($subresult)) {
        echo '<option value="' . $subrow[0] . '">' . $subrow[1] . '</option>';
    }
    echo '</select>';
}
?>
                    
    </tr>
</table>