<?php
use Aura\Html\Escaper as e;
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

$pdo = $GLOBALS['pdo'];

if (isset($_GET['q'])) {
    $q = $_GET['q'];
}

if (isset($_GET['add_value'])) {
    //$add_value = preg_replace('/ /', '', $_GET['add_value']);
    $add_value = $_GET['add_value'];
}

if (isset($_GET['table'])) {
    $table_name = $_GET['table'];
}
?>
    <table border="0">
        <tr>
<?php

        
// Find out if the passed argument matches an actual tablename 
$udf_table_names = "SELECT table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf";
$stmt = $pdo->prepare($udf_table_names);
$stmt->execute();
$udf_tables_names_result = $stmt->fetchAll();

if ($q != "" && $add_value != "add" && $add_value != "edit") {
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
        $white_listed = false;
        foreach ($udf_tables_names_result as $white_list) {
            if ($add_value == $white_list['table_name']) {
                $white_listed = true;
            }
        }
        reset($udf_tables_names_result);
        
        if ($white_listed) {
            $stmt = $pdo->prepare("SELECT * FROM $add_value");
            $stmt->execute();
            $result = $stmt->fetchAll();

            if ($result && $q != 'primary') {
                echo '<table>';
                echo '<tr><th style="padding-left:39px;">' . msg('label_primary_type') . ':</th><td><select name=primary_type class="required" onchange="showdivs(this.value,\'' . e::h($add_value) . '\')">';
                echo '<option value="0">Please select one</option>';
                foreach ($result as $row) {
                    echo '<option value=' . e::h($row[0]) . ' ' . ($row[0] == $q ? "selected" : "") . '>' . e::h($row[1]) . '</option>'; //CHM
                }
                echo '</select></td></tr>';
                echo '</table>';
            }

            if ($q == 'secondary') {
                $table_name = '_secondary';
            } elseif ($q == 'primary') {
                $table_name = '_primary';
            } else {
                $table_name = '_secondary WHERE pr_id = "' . e::h($q) . '"';
            }

            echo '<table>';
            echo '<tr bgcolor="83a9f7">
                  <th>' . msg('button_delete') . '?</th>
                  <th>' . msg('value') . '</th>
                  </tr>';

            if ((((int) $q == $q && (int) $q > 0) || $q == 'primary')) {
                // Find out if the passed argument matches an actual tablename 

                $full_table_name = $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $field_name . $table_name;
                $white_listed = false;
                foreach ($udf_tables_names_result as $white_list) {
                    if ($full_table_name == $white_list['table_name']) {
                        $white_listed = true;
                    }
                }
                if ($white_list) {
                    $stmt = $pdo->prepare("SELECT * FROM $full_table_name");
                    $stmt->execute();
                    $result = $stmt->fetchAll();
                    foreach ($result as $row) {
                        if (isset($bg) && $bg == "FCFCFC") {
                            $bg = "E3E7F9";
                        } else {
                            $bg = "FCFCFC";
                        }
                        echo '<tr bgcolor="' . $bg . '">
                                    <td align=center><input type=checkbox name=x' . e::h($row[0]) . '></td>
                                        <td>' . e::h($row[1]) . '</td>
                                  </tr>';
                    }
                }
            }
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
    $add_table_name = $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $table_name . '_secondary';

    $white_listed = false;
    foreach ($udf_tables_names_result as $white_list) {
        if ($add_table_name == $white_list['table_name']) {
            $white_listed = true;
        }
    }
    if ($white_list) {
        $stmt = $pdo->prepare("SELECT * FROM $add_table_name WHERE pr_id = :q");
        $stmt->execute(array(':q' => $q));
        $result = $stmt->fetchAll();

        echo '<select id="' . e::h($GLOBALS['CONFIG']['db_prefix']) . 'udftbl_' . e::h($table_name) . '_secondary" name="' . e::h($GLOBALS['CONFIG']['db_prefix']) . 'udftbl_' . e::h($table_name) . '_secondary">';
        foreach ($result as $subrow) {
            echo '<option value="' . e::h($subrow[0]) . '">' . e::h($subrow[1]) . '</option>';
        }
        echo '</select>';
    }
}

if ($add_value == "edit") {
    $edit_tablename = $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $table_name . '_secondary';
    $white_listed = false;
    foreach ($udf_tables_names_result as $white_list) {
        if ($edit_tablename == $white_list['table_name']) {
            $white_listed = true;
        }
    }
    if ($white_list) {
        $stmt = $pdo->prepare("Select * FROM $edit_tablename WHERE pr_id = :q");
        $stmt->execute(array(':q' => $q));
        $result = $stmt->fetchAll();

        echo '<select id="' . e::h($GLOBALS['CONFIG']['db_prefix']) . 'udftbl_' . e::h($table_name) . '_secondary" name="' . e::h($GLOBALS['CONFIG']['db_prefix']) . 'udftbl_' . e::h($table_name) . '_secondary">';
        foreach ($result as $subrow) {
            echo '<option value="' . e::h($subrow[0]) . '">' . e::h($subrow[1]) . '</option>';
        }
        echo '</select>';
    }
}
        ?>
                    
    </tr>
</table>