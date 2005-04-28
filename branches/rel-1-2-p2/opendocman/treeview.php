<?php
/*
treeview.php - page for changing personal info
Copyright (C) 2005  Stephen Lawrence, Mitchell Broome 

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
require_once ('class.tree/class.tree.php');

function show_tree($fileid_array, $starting_index = 0, $stoping_index = 5) {
    global $tree;
    global $root;
    if(!$tree){
        //echo "defining in browser\n";
        $tree = new Tree();
        $root  = $tree->open_tree("Directory", $_SERVER['PHP_SELF']);
    }

    $TreeCategory = $_GET['TreeCategory'];
    $index = 0;

    if(isset($fileid_array['0']))
    {
        while($index<sizeof($fileid_array) and $index>=$starting_index and $index<=$stoping_index)
        {
            $file_obj = new FileData($fileid_array[$index], $GLOBALS['connection'], $GLOBALS['database']);
            $fileid = $file_obj->id;
            $realname = $file_obj->getRealname();
            $description = $file_obj->getDescription();
            $modified_date = fix_date($file_obj->getModifiedDate());
            $category = $file_obj->getCategoryName();
            if(!$folders[$category])
            {
                $folders[$category] = $tree->add_folder($root, "$category",$_SERVER['PHP_SELF'], "ftv2/ftv2folderclosed.gif", "ftv2/ftv2folderopen.gif");
            }

            $tree->add_document($folders[$category], "$realname", "details.php?id=$fileid&state=2");
            $index++;
        }

        $tree->close_tree ( );
    }
}
?>
