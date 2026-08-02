<?php
/*
 * Copyright (C) 2000-2025. Stephen Lawrence
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

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Unset all of the session variables.
$_SESSION = array();
// Finally, destroy the session.
session_destroy();
if ($GLOBALS["CONFIG"]["authen"] == 'kerbauth') {
    ?>
    <div class="container py-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Thank you for using OpenDocMan</h4>
            </div>
            <div class="card-body">
                <h5 class="card-title">Logging off...</h5>
                <p class="card-text">
                    OpenDocMan, and other campus web systems, use a cookie to store your credentials for access.
                    This cookie is kept only in your computers memory and not saved to disk for security
                    purposes. In order to remove this cookie from memory you must completely exit your browser.
                    The LOGOUT button below will close the current browser window, but this may not exit your
                    browser software completely.
                </p>
                <p class="card-text">
                    <b>Macintosh Users:</b> Choose 'Quit' from the 'File' menu to be sure the browser is
                    completely exited.
                </p>
                <p class="card-text">
                    <b>PC/Windows Users:</b> Close off all browser windows by clicking the 'X' icon in the
                    upper right of the window. Be sure all browser windows are closed.
                </p>
                <form name="CM">
                    <input type="button" class="btn btn-primary" value="LOGOUT" onclick="top.close();">
                </form>
            </div>
        </div>
    </div>
    <?php

    draw_footer();

} else {// mysql auth, so just kill session and show login prompt
    session_start();
    unset($_SESSION['uid']);

    // Call the plugin API
    callPluginMethod('onAfterLogout');

    header('Location: index');
}
