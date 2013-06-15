<?php
/*
Reviewer_class.php - relates reviewers
Copyright (C) 2013 Stephen Lawrence Jr.
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
class Reviewer extends databaseData
{
    function Reviewer ($id, $connection, $database)
    {
        $this->connection = $connection;
        $this->database = $database;
    }
    function getReviewersForDepartment($dept_id)
    {
        $reviewers = array();
        $query = "SELECT
                            dr.user_id
                      FROM
                            {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer as dr
                      WHERE
                            
                            dr.dept_id = $dept_id
                            ";                         
        $result = mysql_query($query, $this->connection) or die("Error in query during isReviewerForFile call: " . mysql_error());

        $num_rows = mysql_num_rows($result);

        if ($num_rows < 1) {
            return false;
        }
        
        $count = 0;
        while (list($reviewer) = mysql_fetch_row($result)) {
            $reviewers[$count] = $reviewer;
            $count++;
        }

        return $reviewers;
    }

}
