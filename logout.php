<?php
/*
 * Copyright (C) 2000-2021. Stephen Lawrence
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

// Provides logout functionality

include('odm-load.php');

session_start();
// Unset all of the session variables.
$_SESSION = array();
// Finally, destroy the session.
session_destroy();
if ($GLOBALS["CONFIG"]["authen"] == 'kerbauth') {
    ?>
    <html>
    <body bgcolor="#FFFFFF" link="#000000" vlink="#000000" background="images/background_blue.gif">
    <table width="633" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="4">&nbsp;&nbsp;&nbsp;</td>
            <td>
                <table border="0" cellpadding="0" cellspacing="0" width="620">
                    <tr>
                        <td width="100%" align="LEFT" bgcolor="#31639C"><img src="images/blue_left.gif" width="5"
                                                                             height="16" align="top"><b>&nbsp;Thank you
                                for using OpenDocMan</b></td>

                    </tr>
                    <tr>
                        <td width="100%" align="left" bgcolor="#FFCE31">
                            <img src="images/logout_logo.gif" align="left">
                            <h2>Logging off...</h2>
                        </td>
                    </tr>
                    <tr>
                        <td align="left">
                            <img src="/images/white_dot.gif" height="8"><br>
                            OpenDocMan, and other campus web systems, use a cookie to store your credentials for access.
                            This cookie is kept only in your computers memory and not saved to disk for security
                            purposes. In order to remove this cookie from memory you must completely exit your browser.
                            The LOGOUT button below will close the current browser window, but this may not exit your
                            browser software completely.
                            <p>
                                <b>Macintosh Users:</b> Choose 'Quit' from the 'File' menu to be sure the browser is
                                completely exited.
                            <p>
                                <b>PC/Windows Users:</b> Close off all browser windows by clicking the 'X' icon in the
                                upper right of the window. Be sure all browser windows are closed.
                            <p>
                            <p>
                            <form name="CM">
                                <input type="button" value="LOGOUT" Onclick="top.close();">
                                &nbsp;
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php

    draw_footer();

} else {// mysql auth, so just kill session and show login prompt
    session_start();
    unset($_SESSION['uid']);

    // Call the plugin API
    callPluginMethod('onAfterLogout');

    header('Location:index.php');
}
