<?php
// Mitchell Broome
// mbroome@weather.com

//require_once ('config.php');
require_once ('class.tree/class.tree.php');

function show_tree($fileid_array, $starting_index = 0, $stoping_index = 5) {
    global $tree;
    global $root;
    if(!$tree){
        //echo "defining in browser\n";
        $tree = new Tree();
        $root  = $tree->open_tree("Directory", "$PHP_SELF");
    }

    $TreeCategory = $_GET['TreeCategory'];
    $index = 0;

    while($index<sizeof($fileid_array) and $index>=$starting_index and index<=$stoping_index)
    {
        $file_obj = new FileData($fileid_array[$index], $GLOBALS['connection'], $GLOBALS['database']);
        $realname = $file_obj->getRealname();
        $description = $file_obj->getDescription();
        $modified_date = fix_date($file_obj->getModifiedDate());
        $category = $file_obj->getCategoryName();
        if(!$folders[$category]){
            $folders[$category] = $tree->add_folder($root, "$category", 
                    $PHP_SELF, "ftv2/ftv2folderclosed.gif", "ftv2/ftv2folderopen.gif");
        }


        $tree->add_document($folders[$category], "$realname", "details.php?id=$fileid&state=2");
        $index++;
    }

    $tree->close_tree ( );
}

?>
