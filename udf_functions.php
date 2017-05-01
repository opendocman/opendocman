<?php
use Aura\Html\Escaper as e;

/*
udf_functions.php - adds user definced functions
Copyright (C) 2007  Stephen Lawrence Jr., Jonathan Miner
Copyright (C) 2008-2015 Stephen Lawrence Jr.
 
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

if (!defined('udf_functions')) {
    define('udf_functions', 'true', false);

    function udf_add_file_form()
    {
        global $pdo;

        $query = "
          SELECT
            table_name,
            field_type,
            display_name
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}udf
          ORDER BY
            id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array());
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            echo '<tr><td>';
            if (file_exists("udf_help.html")) {
                echo '<a class="body" href="udf_help.html#Add_File_'. e::h($row[2]) .'" onClick="return popup(this,\'Help\')" style="text-decoration:none">'. e::h($row[2]) .'</a>';
            } else {
                echo e::h($row[2]);
            }

            echo '</td><td>';

            //Type is Select List
            if ($row[1] == 1) {
                echo '<select name="'. e::h($row[0]) .'">';
                $query = "
                  SELECT
                    id,
                    value
                  FROM
                    {$row[0]}
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $sub_result = $stmt->fetchAll();

                foreach ($sub_result as $sub_row) {
                    echo '<option value="'. e::h($sub_row[0]) .'">'. e::h($sub_row[1]) .'</option>';
                }
                echo '</select>';
            }

            // Type is Radio
            if ($row[1] == 2) {
                $query = "
                  SELECT
                    id,
                    value
                  FROM
                    {$row[0]}
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $sub_result = $stmt->fetchAll();

                foreach ($sub_result as $sub_row) {
                    echo '<input type=radio name="'. e::h($row[0]) .'" value="'. e::h($sub_row[0]) .'">'. e::h($sub_row[1]);
                }
            }

            // Type is Text
            if ($row[1] == 3) {
                echo '<input tabindex="5" type="Text" name="'. e::h($row[0]) .'" size="16">';
            }
            
            //CHM
            // Type is Sub-Select
            if ($row[1] == 4) {
                $explode_row = explode('_', $row[0]);
                $field_name = $explode_row[2];
                
                $query = "SELECT * FROM {$row[0]}";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $sub_result = $stmt->fetchAll();

                echo '<select name="'. e::h($row[0]) .'" onchange="showdropdowns(this.value, \'add\',\'' . e::h($field_name) . '\')">';
                echo '<option value="">Please select</option>';
                foreach ($sub_result as $sub_row) {
                    echo '<option value="'. e::h($sub_row[0]) .'">'. e::h($sub_row[1]) .'</option>';
                }
                echo '</select>';
                
                echo '<div id="txtHint'. e::h($field_name) .'">Secondary items will show up here.</div>';
            }
            //CHM

            echo '</td></tr>';
        }
    }

    function udf_add_file_insert($fileId)
    {
        global $pdo;

        $query = "
          SELECT
            table_name,
            field_type
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}udf
          ORDER BY
            id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $i = 0; //CHM
        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2 || $row[1] == 3 || $row[1] == 4) { //CHM
                if (isset($_REQUEST[$row[0]]) && $_REQUEST[$row[0]] != "") {
                    $explode_row = explode('_', $row[0]);
                    $field_name = $explode_row[2];

                    $query = "
                      UPDATE
                        {$GLOBALS['CONFIG']['db_prefix']}data
                      SET
                        `{$row['0']}` = :row_value
                      WHERE
                        id = :file_id
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(
                        ':row_value' => $_REQUEST[$row['0']],
                        ':file_id' => $fileId
                    ));

                    //CHM
                    if (isset($_REQUEST['tablename' . $i]) && $_REQUEST['tablename' . $i] != '' && $row[1] == 4) {
                        $secondary_value = intval($_REQUEST[ "{$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary" ]);
                        $query = "
                          UPDATE
                            {$GLOBALS['CONFIG']['db_prefix']}data
                          SET
                            {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary = :secondary_value
                          WHERE
                            id = :file_id
                        ";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(array(
                            ':secondary_value' => $secondary_value,
                            ':file_id' => $fileId
                        ));
                        $i++;
                    }
                    //CHM
                }
            }
        }
    }

    function udf_edit_file_form()
    {
        global $pdo;

        $query = "
          SELECT
            display_name,
            field_type,
            table_name
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}udf
          ORDER BY
            id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array());
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2) {
                echo '<tr><td>' . $row[0] . '</td><td>';
                if ($row[1] == 1) {
                    echo '<select name="'.$row[2].'">';
                }

                $query = "
                  SELECT
                    {$row['2']}
                  FROM
                    {$GLOBALS['CONFIG']['db_prefix']}data
                  WHERE
                    id = :id
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':id' => $_REQUEST['id']));
                $sub_row = $stmt->fetch();
                $sel = $sub_row[0];

                $query = "
                  SELECT
                    id,
                    value
                  FROM
                    {$row[2]}
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $sub_result = $stmt->fetchAll();

                foreach ($sub_result as $sub_row) {
                    if ($row[1] == 1) {
                        echo '<option value="' . e::h($sub_row[0]) . '"';
                        if ($sel == $sub_row[0]) {
                            echo ' selected';
                        }
                        echo '>' . e::h($sub_row[1]) . '</option>';
                    } elseif ($row[1] == 2) {
                        echo '<input type=radio name="' . e::h($row[2]) . '" value="' . e::h($sub_row[0]) . '"';
                        if ($sel == $sub_row[0]) {
                            echo ' checked';
                        }
                        echo '>' . e::h($sub_row[1]);
                    }
                }
                if ($row[1] == 1) {
                    echo '</select>';
                }
                echo '</td></tr>';
            } elseif ($row[1] == 3) {
                echo '<tr><td>' . e::h($row[0]) . '</td><td>';
                $query = "
                  SELECT
                    {$row['2']}
                  FROM
                    {$GLOBALS['CONFIG']['db_prefix']}data
                  WHERE
                    id = :id
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':id' => $_REQUEST['id']));
                $sub_row = $stmt->fetch();

                echo '<input type="text" name="' . e::h($row[2]) . '" size="50" value="' . e::h($sub_row[0]) . '">';
            }
            //CHM
            elseif ($row[1] == 4) {
                $explode_row = explode('_', $row[2]);
                $field_name = $explode_row[2];
                
                echo '<tr><td>' . e::h($row[0]) . '</td><td>';
                echo '<select name="'. e::h($row[2]) .'"  onchange="showdropdowns(this.value, \'edit\',\'' . e::h($field_name) . '\')">';
                echo '<option value="">Please select one</option>';

                $query = "
                  SELECT
                    {$row['2']}
                  FROM
                    {$GLOBALS['CONFIG']['db_prefix']}data
                  WHERE
                    id = :id
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':id' => $_REQUEST['id']));
                $sub_row = $stmt->fetch();

                $sel_pri = $sub_row[0];

                $query = "SELECT id, value FROM {$row[2]}";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $sub_result = $stmt->fetchAll();

                foreach ($sub_result as $sub_row) {
                    if ($row[1] == 4) {
                        echo '<option value="' . e::h($sub_row[0]) . '"';
                        if ($sel_pri == $sub_row[0]) {
                            echo ' selected';
                        }
                        echo '>' . e::h($sub_row[1]) . '</option>';
                    }
                }
                echo '</select>';
                
                echo '</td></tr>';
                
                //secondary dropdown
                echo '<tr><td>&nbsp;</td><td><div id="txtHint'. e::h($field_name) .'">';
                
                $query = "
                  SELECT
                    {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary
                  FROM
                    {$GLOBALS['CONFIG']['db_prefix']}data
                  WHERE
                    id = :id
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':id' => $_REQUEST['id']));
                $sub_row = $stmt->fetch();

                $sel = $sub_row[0];
                
                if ($sel =='') {
                    echo 'Secondary items will show up here.';
                } else {
                    $query = "
                      SELECT
                        id,
                        value
                      FROM
                        {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary
                      WHERE
                        pr_id = :sel_pri
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(':sel_pri' => $sel_pri));
                    $sub_result = $stmt->fetchAll();

                    echo '<select id="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_'. e::h($field_name) .'_secondary" name="' . $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_'. e::h($field_name) .'_secondary">';
                    foreach ($sub_result as $sub_row) {
                        if ($row[1] == 4) {
                            echo '<option value="' . e::h($sub_row[0]) . '"';
                            if ($sel == $sub_row[0]) {
                                echo ' selected';
                            }
                            echo '>' . e::h($sub_row[1]) . '</option>';
                        }
                    }
                }
                echo '</select>';
                echo '</div></td></tr>';
            }
            //CHM
        }
    }

    function udf_edit_file_update()
    {
        global $pdo;

        $query = "
          SELECT
            display_name,
            field_type,
            table_name
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}udf
          ORDER BY
          id
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $i = 0; //CHM
        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2 || $row[1] == 3 || $row[1] == 4) { //CHM sub select option 4 added
                if (isset($_REQUEST[$row[2]]) && $_REQUEST[$row[2]] != "") {
                    $query = "
                      UPDATE
                        {$GLOBALS['CONFIG']['db_prefix']}data
                      SET
                        `{$row['2']}` = :row2
                      WHERE
                        id = :id
                    ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(
                        ':id' => $_REQUEST['id'],
                        ':row2' => $_REQUEST[$row['2']]
                    ));

                    //CHM secondary values
                    if ((isset($_REQUEST['tablename' . $i]) && $_REQUEST['tablename' . $i] != '') && $row[1] == 4) {
                        $explode_row = explode('_', $row[2]);
                        $field_name = $explode_row[2];
                        $secondary_value = intval($_REQUEST[ "{$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary" ]);
                        $query = "
                          UPDATE
                            {$GLOBALS['CONFIG']['db_prefix']}data
                          SET
                            `{$GLOBALS['CONFIG']['db_prefix']}udftbl_{$field_name}_secondary`= :secondary_value
                          WHERE
                            id = :id
                        ";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute(array(
                            ':secondary_value' => $secondary_value,
                            ':id' => $_REQUEST['id']
                        ));

                        $i++;
                    }
                }
            }
        }
    }

    /**
     * Generate the UDF details display 
     * @param type $fileId
     * @return string
     */
    function udf_details_display($fileId)
    {
        global $pdo;

        $return_string = null;
        
        $query = "SELECT display_name,field_type,table_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array());
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2) {
                $query = "SELECT value FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$row['2']} WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = :file_id AND {$GLOBALS['CONFIG']['db_prefix']}data.{$row['2']}={$row['2']}.id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':file_id' => $fileId));
                $sub_row = $stmt->fetch();

                if ($stmt->rowCount() > 0) {
                    $return_string .= '<th valign=top align=right>' . e::h($row[0]) . ':</th><td>' . e::h($sub_row[0]) . '</td></tr>';
                }
            } elseif ($row[1] == 3) {
                $query = "SELECT {$row[2]} FROM {$GLOBALS['CONFIG']['db_prefix']}data WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = :file_id ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':file_id' => $fileId));
                $sub_row = $stmt->fetch();

                if ($stmt->rowCount() > 0) {
                    $return_string .=  '<th valign=top align=right>' . e::h($row[0]) . ':</th><td>' . e::h($sub_row[0]) . '</td></tr>';
                }
            }
            //CHM
            elseif ($row[1] == 4) {
                $query = "SELECT value FROM {$GLOBALS['CONFIG']['db_prefix']}data, {$row['2']} WHERE {$GLOBALS['CONFIG']['db_prefix']}data.id = :file_id AND {$GLOBALS['CONFIG']['db_prefix']}data.{$row['2']}={$row['2']}.id";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':file_id' => $fileId));
                $sub_row = $stmt->fetch();

                if ($stmt->rowCount() > 0) {
                    $return_string .= '<th valign=top align=right>' . e::h($row[0]) . ':</th><td>' . e::h($sub_row[0]) . '</td></tr>';
                }
            }
            //CHM
        }
        return $return_string;
    }

    function udf_admin_header()
    {
        echo '<th bgcolor ="#83a9f7"><font color="#FFFFFF">' .msg('label_user_defined_fields'). '</font></th>';
    }

    function udf_admin_menu()
    {
        global $pdo;

        echo '<td valign=top><table border=0>';
        echo '<tr><td><b><a href="udf.php?submit=add&state=' . (e::h($_REQUEST['state'] + 1)).'">' .msg('label_add'). '</a></b></td></tr>';
        echo '<tr><td><b><a href="udf.php?submit=deletepick&state=' . (e::h($_REQUEST['state'] + 1)).'">' .msg('label_delete'). '</a></b></td></tr>';
        echo '<tr><td><hr></td></tr>';
        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            echo '<tr><td><b><a href="udf.php?submit=edit&udf='. e::h($row[0]) .'&state=' . (e::h($_REQUEST['state'] + 1)).'">'. e::h($row[2]) .'</a></b></td></tr>';
        }
        echo '</table></td>';
    }

    function udf_functions_java_menu()
    {
        global $pdo;

        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf order by id";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach($result as $row) {
            if ( $row[1] == 1 || $row[1] == 2 || $row[1] == 3 )
            {
                echo "case '". e::h($row[2]) ."':".PHP_EOL;
                echo "      info_Array = ". e::h($row[0]) ."_array;".PHP_EOL;
                echo "      break;".PHP_EOL;
            }
        }
    }

    function udf_functions_java_array()
    {
        global $pdo;

        $query = "SELECT table_name,field_type FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2) {
                $query = "SELECT id,value FROM {$row[0]}";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array());
                $sub_result = $stmt->fetchAll();

                echo $row[0] . "_array = new Array();".PHP_EOL;              
                $index = 0;
                foreach($sub_result as $sub_row) {
                    echo "\t" . e::h($row[0]) . "_array[" . e::h($index) . "] = new Array(\"" . e::h($sub_row[1]) . "\", " . e::h($sub_row[0]) . ");".PHP_EOL;
                    $index++;
                }
            }
        }
    }

    function udf_functions_java_options($id)
    {
        global $pdo;

        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf order by id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array());
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            if ($row[1] == 1 || $row[1] == 2) {
                echo '<option id="'. e::h($id) .'" value="'. e::h($row[2]) .'">'. e::h($row[2]) .'</option>';
                $id++;
            }
        }
    }

    function udf_functions_add_udf()
    {
        global $pdo;

        if (empty($_REQUEST['table_name'])) {
            header('Location: admin.php?last_message=' . urlencode(msg('message_udf_cannot_be_blank')));
            exit;
        }

        if (empty($_REQUEST['display_name'])) {
            header('Location: admin.php?last_message=' . urlencode(msg('message_udf_cannot_be_blank')));
            exit;
        }
        
        $table_name = str_replace(' ', '', $GLOBALS['CONFIG']['db_prefix'] . 'udftbl_' . $_REQUEST['table_name']);

        if(!is_valid_udf_name($table_name))
        {
            header('Location: admin.php?last_message=Error+:+Invalid+Name+(A-Z 0-9 Only)');
            exit;
        }

        // Check for duplicate table name
        $query = "SELECT * FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE table_name = :table_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array(':table_name' => $table_name));

        if ($stmt->rowCount() == 0) {
            if ($_REQUEST['field_type'] == 1 || $_REQUEST['field_type'] == 2) {
                // They have chosen Select list of Radio list
                // 
                // First we add a new column in the data table
                $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data ADD COLUMN $table_name int AFTER category";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(':table_name' => $table_name));

                if (!$stmt) {
                    header('Location: admin.php?last_message=Error+:+Problem+With+Alter');
                    exit;
                }

                // Now we need to create a new table to store the UDF Info
                $query = "CREATE TABLE $table_name ( id int auto_increment unique, value varchar(64) )";
                $stmt = $pdo->prepare($query);
                $stmt->execute();

                if (!$stmt) {
                    // If the CREATE fails, rollback the ALTER
                    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN $table_name";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    
                    header('Location: admin.php?last_message=Error+:+Problem+With+Create');
                    exit;
                }

                // And finally, add an entry into the udf table
                $query = "
                    INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}udf
                    (
                      table_name,
                      display_name,
                      field_type
                    ) VALUES (
                      :table_name,
                      :display_name,
                      :field_type
                      )
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':table_name' => $table_name,
                    ':display_name' => $_REQUEST['display_name'],
                    ':field_type' => $_REQUEST['field_type']
                ));

                if (!$stmt) {
                    // If the INSERT fails, rollback the CREATE and ALTER
                    $query = "DROP TABLE $table_name";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();

                    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN $table_name";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();

                    header('Location: admin.php?last_message=Error+:+Problem+With+INSERT');
                    exit;
                }
            } elseif ($_REQUEST['field_type'] == 4) {
                // They have chosen Select list of Radio list
                //
                $primary_table_name = "{$table_name}_primary";

                $query = "SHOW COLUMNS FROM {$GLOBALS['CONFIG']['db_prefix']}data LIKE :primary_table_name";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':primary_table_name' => $primary_table_name
                ));
                $count = $stmt->rowCount();

                if ($count == 0) {

                    // First we add a new column in the data table
                    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data
                          ADD COLUMN
                            {$table_name}_primary int AFTER category,
						  ADD COLUMN
						    {$table_name}_secondary int AFTER {$table_name}_primary";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    if (!$stmt) {
                        header('Location: admin.php?last_message=Error+:+Problem+With+Alter');
                        exit;
                    }

                    // Now we need to create a new table to store the UDF Info
                    $query = "CREATE TABLE {$table_name}_primary ( id int auto_increment unique, value varchar(64) )";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    if (!$stmt) {
                        // If the CREATE fails, rollback the ALTER
                        $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$table_name}_primary";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        header('Location: admin.php?last_message=Error+:+Problem+With+Create');
                        exit;
                    }

                    $query = "CREATE TABLE {$table_name}_secondary ( id int auto_increment unique, value varchar(64), pr_id int )";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    if (!$stmt) {
                        // If the CREATE fails, rollback the ALTER
                        $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$table_name}_secondary";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        header('Location: admin.php?last_message=Error+:+Problem+With+Create');
                        exit;
                    }

                    // And finally, add an entry into the udf table
                    $query = "
                  INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}udf
                      (
                        table_name,
                        display_name,
                        field_type
                      ) VALUES (
                        :table_name,
                        :display_name,
                        :field_type
                      )
                ";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute(array(
                        ':table_name' => $table_name . '_primary',
                        ':display_name' => $_REQUEST['display_name'],
                        ':field_type' => $_REQUEST['field_type']
                    ));
                    if (!$stmt) {
                        // If the INSERT fails, rollback the CREATE and ALTER
                        $query = "DROP TABLE {$table_name}_primary";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        $query = "DROP TABLE {$table_name}_secondary";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$table_name}_primary, DROP COLUMN {$table_name}_secondary";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        header('Location: admin.php?last_message=Error+:+Problem+With+INSERT');
                        exit;
                    }
                } else {
                    header('Location: admin.php?last_message=Error+:+Duplicate+UDF+Name');
                    exit;
                }
            } elseif ($_REQUEST['field_type'] == 3) {
                // The have chosen a text field
                $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data ADD COLUMN {$table_name} varchar(255) AFTER category";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                if (!$stmt) {
                    header('Location: admin.php?last_message=Error+:+Problem+With+Alter');
                    exit;
                }

                $query = "
                  INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}udf
                  (
                    table_name,
                    display_name,
                    field_type
                  ) VALUES (
                    :table_name,
                    :display_name,
                    :field_type
                  )";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                    ':table_name' => $table_name,
                    ':display_name' => $_REQUEST['display_name'],
                    ':field_type' => $_REQUEST['field_type']
                ));
                if (!$stmt) {
                    // If the INSERT fails, rollback the ALTER
                    $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$table_name}";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute();
                    
                    header('Location: admin.php?last_message=Error+:+Problem+With+INSERT');
                    exit;
                }
            }
        } else {
            header('Location: admin.php?last_message=Error+:+Duplicate+Table+Name');
            exit;
        }
    }

    function udf_functions_delete_udf()
    {
        global $pdo;

        if(!is_valid_udf_name($_REQUEST['id'])) {
            header('Location: admin.php?last_message=Error+:+Invalid+Name+(A-Z 0-9 Only)');
            exit;
        }
        
        $request = $_REQUEST['id'];
        
        // If we are deleting a sub-select, we have two entries to delete
        // , a _primary, and a _secondary
        if(isset($_REQUEST['type']) && $_REQUEST['type'] == 4) {
            $explode_row = explode('_', $request);
           
            $subselect_table_name = $explode_row[2];
            foreach (array('primary', 'secondary') as $loop) {
                $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE table_name LIKE :like ";
                $stmt = $pdo->prepare($query);
                $stmt->execute(array(
                   ':like' => "%{$subselect_table_name}_{$loop}"
                ));

                $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$subselect_table_name}_{$loop}";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                
                $query = "DROP TABLE IF EXISTS {$GLOBALS['CONFIG']['db_prefix']}udftbl_{$subselect_table_name}_{$loop}";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
            }
        } else {

            $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE table_name = :id ";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                ':id' => $request
            ));

            $query = "ALTER TABLE {$GLOBALS['CONFIG']['db_prefix']}data DROP COLUMN $request";
            $stmt = $pdo->prepare($query);
            $stmt->execute();

            $query = "DROP TABLE IF EXISTS $request";
            $stmt = $pdo->prepare($query);
            $stmt->execute();
        }
    }

    function udf_functions_search_options()
    {
        global $pdo;

        $query = "SELECT table_name,field_type,display_name FROM {$GLOBALS['CONFIG']['db_prefix']}udf ORDER BY id";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach ($result as $row) {
            echo '<option value="'. e::h($row[2]) .'">'. e::h($row[2]) .'</option>';
        }
    }

    /**
     * Perform search on UDF fields
     * @param string $where
     * @param string $query_pre
     * @param string $query
     * @param string $equate
     * @param string $keyword
     * @return array
     */
    function udf_functions_search($where, $query_pre, $query, $equate, $keyword)
    {
        global $pdo;

        $lookup_query = "SELECT table_name,field_type FROM {$GLOBALS['CONFIG']['db_prefix']}udf WHERE display_name = :display_name ";
        $stmt = $pdo->prepare($lookup_query);
        $stmt->execute(array(
            ':display_name' => $where
        ));
        $row = $stmt->fetch();

        if ($row[1] == 1 || $row[1] == 2 || $row[1] == 4) {
            $query_pre .= ', ' . $row[0];
            $query .= $row[0] . '.value' . $equate . '\'' . $keyword . '\'';
            $query .= ' AND d.' . $row[0] . ' = ' . $row[0] . '.id';
        } elseif ($row[1] == 3) {
            $query .= $row[0] . $equate . '\'' . $keyword . '\'';
        }

        return array($query_pre,$query);
    }

    /**
     * @param string $name
     * @return int
     */
    function is_valid_udf_name($name) 
    {
        return preg_match('/^\w+$/', $name);
    }
}
