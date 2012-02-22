<?php
/**
 * Plugin_class should be used as an abstract class to create your own plugins.
 * See the README file in the HelloWorld plugin folder for details
 * 
    Copyright (C) 2010-2011 Stephen Lawrence Jr.

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
 *
 * @author Stephen J. Lawrence Jr.
 */
class Plugin 
{

    var $name = '';
    var $version = '';
    var $author = '';
    var $homepage = '';
    var $description = '';
    var $pluginslist = '';
    
    function Plugin()
    {
        $name = $this->name;
        $version = $this->version;
        $author = $this->author;
        $homepage = $this->homepage;
        $description = $this->description;
        $pluginslist = $this->pluginslist;
        $this->loadPlugins();
    }

    /*
     * INCLUDE ALL PLUGINS
     * @return array $pluginslist An array of plugin names currently in the plug-ins folder
     */
    function getPluginsList()
    {
        $pluginslist = array();
        $curdir = dirname(__FILE__);
        if ($handle = opendir($curdir . '/plug-ins'))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ($file != '.htaccess' && $file != "." && $file != ".." && $file != '.svn' && is_file('plug-ins/' . $file . '/' . $file . '_class.php'))
                {
                    array_push($pluginslist, $file);
                }
            }
            $this->setPluginsList($pluginslist);
            return $pluginslist;
        }
    }

    /*
     * Set the value for the pluginslist variable
     */
    function setPluginsList($var)
    {
        $this->pluginslist = $var;
    }

    /*
     * Include all the plugin class files
     * @return true
     */
    function loadPlugins()
    {
        foreach($this->getPluginsList() as $file)
        {
            include_once('plug-ins/' . $file . '/' . $file . '_class.php');
        }
        return true;
    }

    /*
     * This function allows for new admin menu items to display for your plugin
     */
    function onAdminMenu() {}

    /*
     * This function is run on the Add File page
     */
    function onBeforeAdd() {}

    /*
     * This function is run on while the file is being added to the database
     */
    function onDuringAdd($fileid) {}

    /*
     * This function is run after a new file is added
     */
    function onAfterAdd($fileid) {}

    /*
     * This function is run before the edit file form is finished being rendered
     */
    function onBeforeEditFile($fileid) {}

    /*
     * This function is run after the user saves and change to a file
     */
    function onAfterEditFile($fileid) {}

    /*
     * This function is run after the user deletes a file (aka archive)
     */
    function onAfterArchiveFile() {}

    /*
     * This function is run after the admin permanently deletes a file
     */
    function onAfterDeleteFile() {}

    /*
     * This function is run before a user is logged in
     */
    function onBeforeLogin() {}

    /*
     * This function is run after a user is logged in
     */
    function onAfterLogin() {}

    /*
     * This function is run after the user session is cleared
     */

    function onAfterLogout(){}

    /*
     * This function is called after a failed login
     */
    function onFailedLogin(){}

    /*
     * This function is called after the user views a file
     */
    function onViewFile() {}

    /*
     * This function is performed after a search has been initiated
     */
    function onSearch() {}

    /*
     * This function is run at the top of the add user form
     */
    function onBeforeAddUser() {}

    /*
     * This function is run after the add user form is saved
     */
    function onAfterAddUser() {}

    /*
     * This function allows for setting of class settings
     */
    function setProperties(){}

    /*
     * This function allows for getting of class settings
     */
    function getProperties(){}

    /*
     * This function is run during the details view
     */
    function onDuringDetails($fileid){}

    /*
     * This function is run before the file list view
     */
    function onBeforeListFiles($fileList){}

    /*
     * This function is run after the file list view is drawn
     */
    function onAfterListFiles() {}

    /*
     * This function is run before the edited file object is saved to the db
     */
    function onBeforeEditFileSaved() {}
}
