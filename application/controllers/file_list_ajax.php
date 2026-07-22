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

// AJAX endpoint for paginated file listing (Tabulator remote pagination)
// Uses batch SQL queries to avoid N+1 per-file queries

use Aura\Html\Escaper as e;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// DEBUG
file_put_contents('/tmp/file_list_ajax_debug.log', "Script reached at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

$pdo = $GLOBALS['pdo'];
$db_prefix = $GLOBALS['CONFIG']['db_prefix'];

$page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
$size = isset($_REQUEST['size']) ? max(1, min(500, (int)$_REQUEST['size'])) : 25;
$state = isset($_REQUEST['state']) ? (int)$_REQUEST['state'] : 1;

$user_obj = new User($_SESSION['uid'], $pdo);
$user_perms = new UserPermission($_SESSION['uid'], $pdo);
$is_admin = $user_obj->isAdmin();

// Step 1: Get all viewable file IDs (permission check, fast integer query)

// Search mode — overrides state when keyword is provided
if (isset($_REQUEST['keyword']) && $_REQUEST['keyword'] !== '') {
    $keyword_raw = $_REQUEST['keyword'];
    $where = $_REQUEST['where'] ?? 'all';
    $exact_phrase = $_REQUEST['exact_phrase'] ?? '';
    $case_sensitivity = $_REQUEST['case_sensitivity'] ?? '';
    $is_udf = false;

    $equate = ($case_sensitivity === 'on') ? ' LIKE BINARY ' : ' LIKE ';
    $search_keyword = ($exact_phrase === 'on') ? $keyword_raw : '%' . $keyword_raw . '%';

    $query_pre = "SELECT d.id FROM {$db_prefix}data as d, {$db_prefix}user as u, {$db_prefix}department dept, {$db_prefix}category as c ";
    $query = "WHERE d.owner = u.id AND d.department = dept.id AND d.category = c.id AND (";

    switch ($where) {
        case 'category':
            $query .= "c.name $equate :keyword ";
            break;
        case 'author':
            if ($exact_phrase === 'on') {
                $space_pos = strpos($keyword_raw, ' ');
                $author_last_name = ($space_pos !== false) ? substr($keyword_raw, 0, $space_pos) : $keyword_raw;
                $author_first_name = ($space_pos !== false) ? substr($keyword_raw, $space_pos + 1) : '';
                $query .= " u.first_name $equate :author_first_name AND u.last_name $equate :author_last_name ";
            } else {
                $query .= " u.first_name $equate :keyword OR u.last_name $equate :keyword ";
            }
            break;
        case 'department':
            $query .= "dept.name $equate :keyword ";
            break;
        case 'descriptions':
            $query .= "d.description $equate :keyword ";
            break;
        case 'filenames':
            $query .= "d.realname $equate :keyword ";
            break;
        case 'comments':
            $query .= "d.comment $equate :keyword ";
            break;
        case 'file_id':
            $query .= "d.id $equate :keyword ";
            break;
        case 'all':
            $query .= "c.name $equate :keyword OR u.first_name $equate :keyword OR u.last_name $equate :keyword OR dept.name $equate :keyword OR d.description $equate :keyword OR d.realname $equate :keyword OR d.comment $equate :keyword ";
            break;
        default:
            $is_udf = true;
            list($query_pre, $query) = udf_functions_search($where, $query_pre, $query, $equate, $search_keyword);
            break;
    }

    $query .= ") ORDER BY d.id ASC";
    $final_query = $query_pre . $query;

    $stmt = $pdo->prepare($final_query);
    if ($where === 'author' && $exact_phrase === 'on') {
        $stmt->bindValue(':author_first_name', $author_first_name);
        $stmt->bindValue(':author_last_name', $author_last_name);
    } elseif (!$is_udf) {
        $stmt->bindValue(':keyword', $search_keyword);
    }
    $stmt->execute();
    $search_results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Intersect with viewable file IDs for permission filtering
    $viewable_ids = $user_perms->getViewableFileIds(false);
    $file_id_array = array_values(array_intersect($search_results, $viewable_ids));
} else {
switch ($state) {
    case 2: // archived files (view_del_archive)
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        $query = "SELECT id FROM {$db_prefix}data WHERE publishable = 2";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $file_id_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
        break;
    case 0: // pending review (toBePublished)
        if (!$user_obj->isReviewer()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        if ($is_admin) {
            $query = "SELECT id FROM {$db_prefix}data WHERE publishable = 0";
        } else {
            $query = "SELECT d.id FROM {$db_prefix}data d "
                   . "JOIN {$db_prefix}dept_reviewer dr ON dr.dept_id = d.department AND dr.user_id = :uid "
                   . "WHERE d.publishable = 0";
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($is_admin ? [] : [':uid' => $_SESSION['uid']]);
        $file_id_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
        break;
    case -1: // rejected files (rejects)
        if ($is_admin) {
            $query = "SELECT id FROM {$db_prefix}data WHERE publishable = -1";
        } else {
            $query = "SELECT id FROM {$db_prefix}data WHERE publishable = -1 AND owner = :uid";
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($is_admin ? [] : [':uid' => $_SESSION['uid']]);
        $file_id_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
        break;
    case 3: // checked-out files (file_ops view_checkedout)
        if (!$user_obj->isRoot()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        $query = "SELECT id FROM {$db_prefix}data WHERE status > 0";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $file_id_array = $stmt->fetchAll(PDO::FETCH_COLUMN);
        break;
    default: // state=1 (active files) and any other
        $file_id_array = $user_perms->getViewableFileIds(false);
        break;
}
}
$total_files = count($file_id_array);
$last_page = max(1, (int)ceil($total_files / $size));

// Step 2: Slice for the current page
$offset = ($page - 1) * $size;
$page_ids = array_slice($file_id_array, $offset, $size);

if (empty($page_ids)) {
    header('Content-Type: application/json');
    echo json_encode(array('data' => array(), 'last_page' => 1, 'last_row' => 0));
    exit;
}

// Build parameterized IN clause
$in_placeholders = implode(',', array_fill(0, count($page_ids), '?'));
$page_params = array_values($page_ids);

// Step 3: Batch load file data with owner name and department name (replaces N*FileData + N*getOwnerFullName + N*getDeptName)
$query = "
    SELECT
        d.id, d.realname, d.description, d.status,
        d.created, d.owner, d.department,
        u.first_name, u.last_name,
        dept.name AS dept_name
    FROM {$db_prefix}data d
    LEFT JOIN {$db_prefix}user u ON d.owner = u.id
    LEFT JOIN {$db_prefix}department dept ON d.department = dept.id
    WHERE d.id IN ($in_placeholders)
";
$stmt = $pdo->prepare($query);
$stmt->execute($page_params);
$file_rows = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $file_rows[$row['id']] = $row;
}

// Step 4: Batch load modified dates (replaces N*getModifiedDate)
$query = "
    SELECT id, MAX(modified_on) AS modified_on
    FROM {$db_prefix}log
    WHERE id IN ($in_placeholders)
    GROUP BY id
";
$stmt = $pdo->prepare($query);
$stmt->execute($page_params);
$modified_dates = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $modified_dates[$row['id']] = $row['modified_on'];
}

// Step 5: Batch load user permissions (replaces N*getPermission on User_Perms)
$query = "
    SELECT fid, rights
    FROM {$db_prefix}user_perms
    WHERE uid = ? AND fid IN ($in_placeholders)
";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge(array($_SESSION['uid']), $page_params));
$user_perms_map = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user_perms_map[$row['fid']] = (int)$row['rights'];
}

// Step 6: Batch load department permissions (replaces N*getPermission on Dept_Perms)
$query = "
    SELECT fid, rights
    FROM {$db_prefix}dept_perms
    WHERE dept_id = ? AND fid IN ($in_placeholders)
";
$stmt = $pdo->prepare($query);
$stmt->execute(array_merge(array($user_obj->getDeptId()), $page_params));
$dept_perms_map = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dept_perms_map[$row['fid']] = (int)$row['rights'];
}

// Step 7: Batch load reviewer assignments (replaces N*isReviewerForFile)
$reviewer_file_ids = array();
if (!$is_admin) {
    $query = "
        SELECT d.id
        FROM {$db_prefix}data d
        JOIN {$db_prefix}dept_reviewer dr ON dr.dept_id = d.department AND dr.user_id = ?
        WHERE d.id IN ($in_placeholders)
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge(array($_SESSION['uid']), $page_params));
    $reviewer_file_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $reviewer_file_ids = array_flip($reviewer_file_ids);
}

// Step 8: Build response data from batch-loaded arrays
$data = array();
foreach ($page_ids as $fileid) {
    if (!isset($file_rows[$fileid])) {
        continue;
    }
    $row = $file_rows[$fileid];

    // Replicate getAuthority() logic without per-file queries
    if ($is_admin || isset($reviewer_file_ids[$fileid])) {
        $userAccessLevel = 4; // ADMIN_RIGHT
    } elseif ((int)$row['owner'] === (int)$_SESSION['uid'] && (int)$row['status'] !== 0) {
        $userAccessLevel = 3; // WRITE_RIGHT for owner of locked file
    } else {
        $access = isset($user_perms_map[$fileid]) ? $user_perms_map[$fileid] : -999;
        if ($access >= 0 && $access <= 4) {
            $userAccessLevel = $access;
        } else {
            $userAccessLevel = isset($dept_perms_map[$fileid]) ? $dept_perms_map[$fileid] : 0;
        }
    }

    // Lock: file is locked if status != 0 OR user lacks VIEW_RIGHT
    $lock = !((int)$row['status'] === 0 && $userAccessLevel >= 1); // VIEW_RIGHT = 1

    $description = $row['description'];
    if ($description === null || $description === '') {
        $description = msg('message_no_description_available');
    } else {
        $description = stripslashes($description);
    }

    $created_date = fix_date($row['created']);
    if (isset($modified_dates[$fileid]) && $modified_dates[$fileid]) {
        $modified_date = fix_date($modified_dates[$fileid]);
    } else {
        $modified_date = $created_date;
    }

    $owner_name = (isset($row['last_name']) ? $row['last_name'] : '') . ', ' . (isset($row['first_name']) ? $row['first_name'] : '');
    $realname = $row['realname'];

    if ($userAccessLevel >= 2) { // READ_RIGHT
        $suffix = strtolower(substr($realname, strrpos($realname, '.') + 1));
        $mimetype = File::mime_by_ext($suffix);
        $view_link = 'view_file?submit=view&id=' . urlencode(e::h($fileid)) . '&mimetype=' . urlencode($mimetype);
    } else {
        $view_link = 'none';
    }

    $filesize = display_filesize(getFilePath($fileid, $realname, 'data'));
    $details_link = 'details?id=' . e::h($fileid) . '&state=' . e::h($_GET['state'] ?? 1);

    $data[] = array(
        'id' => $fileid,
        'view_link' => $view_link,
        'details_link' => $details_link,
        'filename' => $realname,
        'description' => $description,
        'created_date' => $created_date,
        'modified_date' => $modified_date,
        'owner_name' => $owner_name,
        'dept_name' => $row['dept_name'] ?? '',
        'filesize' => $filesize,
        'lock' => $lock,
    );
}

header('Content-Type: application/json');
echo json_encode(array(
    'data' => $data,
    'last_page' => $last_page,
    'last_row' => $total_files,
));
exit;
