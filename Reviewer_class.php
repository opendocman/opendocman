<?php
/*
Reviewer_class.php - relates reviewers
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
class Reviewer extends databaseData
{
    protected $connection;

    public function Reviewer($id, PDO $pdo)
    {
        $this->id = $id;
        $this->connection = $pdo;
    }
    public function getReviewersForDepartment($dept_id)
    {
        $reviewers = array();
        $query = "
          SELECT
            dr.user_id
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer as dr
          WHERE
            dr.dept_id = :dept_id
        ";
        $stmt = $this->connection->prepare($query);
        $stmt->execute(array(
            ':dept_id' => $dept_id
        ));
        $result = $stmt->fetchAll();

        $num_rows = $stmt->rowCount();

        if ($num_rows < 1) {
            return false;
        }
        
        $count = 0;
        foreach ($result as $row) {
            $reviewers[$count] = $row['user_id'];
            $count++;
        }

        return $reviewers;
    }
}
