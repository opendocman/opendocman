<?php

// +--------------------------------------------------------------------------+
// | crumb version 0.1.0.1 - 2003/01/04                                       |
// | by Michael J. Pawlowsky <mjpawlowsky@yahoo.com>                          |
// +--------------------------------------------------------------------------+
// | Copyright (c) 2003 RC Online Canada                                      |
// +--------------------------------------------------------------------------+
// | License:  GNU/GPL - http://www.gnu.org/copyleft/gpl.html                 |
// +--------------------------------------------------------------------------+
// | Original release available on PHP Classes:                               |
// |    http://www.phpclasses.org/                                            |
// |                                                                          |
// +--------------------------------------------------------------------------+
//
// 2003/01/04 - 0.1.0.1 fixed undefined tstr in addCrumb


class crumb
{


    /**
     * @return void
     * @param level int
     * @param title string
     * @param url string
     * @param post boolean
     * @desc Add a bread crumb to the session array. If post is true add the $_POST args to the URL.
     */
    function addCrumb($level, $title, $url, $post = false)
    {

        $tstr = "";

        if (isset($_SESSION['crumbs'][$level]))
        {
            unset($_SESSION['crumbs'][$level]);
        }

        if($post)
        {
            if(strpos($url,"?"))
            {
                $tstr = "&";
            }else
            {
                $tstr = "?";
            }

            foreach($_POST as $key => $value)
            {
                $tstr.=$key."=".urlencode($value)."&";
            }
            // pop off the last &
            $tstr = rtrim ($tstr, "&");
        }


        $tmp = array("title" => $title, "url" => $url . $tstr);
        $_SESSION['crumbs'][$level] = $tmp;
    } //end addCrumb()


    /**
     * @return void
     * @param level int
     * @desc Deletes a bread crumb.
     */
    function delCrumb($level)
    {
        if (isset($_SESSION['crumbs'][$level]))
        {
            unset($_SESSION['crumbs'][$level]);
        }
    } //end delCrumb()


    /**
     * @return string $trail
     * @param cur_level int
     * @desc Print out the current crumb trail from $cur_level on down.
     */
    function printTrail($cur_level)
    {
        $trail = "<span class=\"crumb\">";
        for ($i=1; $i != $cur_level+1; $i++)
        {

            if (isset($_SESSION['crumbs'][$i]))
            {
                if ($i != $cur_level)
                {
                    $trail .= "<a class=\"statusbar\" href=\"". $_SESSION['crumbs'][$i]['url'] . '">';
                    $trail .= $_SESSION['crumbs'][$i]['title'];
                    $trail .= "</a>";
                }else
                {
                    $trail .= '<span class="statusbar">' . $_SESSION['crumbs'][$i]['title'] . '</span>';
                    $trail .= "</span>";
                }
                if ($i != $cur_level)
                {
                    $trail .= "<FONT class=\"statusbar\">&nbsp;&gt;&nbsp;</FONT>";
                }
            }
        }
        $trail .= "</span>";
        
        return $trail;
    } // end printTrail()

} //end class crumb

// #################  Example #############################

//Page1
// $crumb = new crumb();
// $crumb->addCrumb(1, "Review", $_SERVER['REQUEST_URI']);	
// $crumb->printTrail(1);

//Page2
// $crumb = new crumb();
// $crumb->addCrumb(2, "Review", $_SERVER['REQUEST_URI']);
// $crumb->printTrail(2);	

//Page3 - Is the results from a POST form.
// $crumb = new crumb();
// $crumb->addCrumb(3, "Review", $_SERVER['REQUEST_URI']), 1;
// $crumb->printTrail(3);

//Page4
// $crumb = new crumb();
// $crumb->addCrumb(4, "Review", $_SERVER['REQUEST_URI']);
// $crumb->printTrail(4);