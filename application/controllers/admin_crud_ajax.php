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

// AJAX endpoint for admin CRUD tables (Users, Departments, Categories)
// Tabulator remote pagination + JSON mutation endpoints

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    return;
}

$pdo = $GLOBALS['pdo'];
$db_prefix = $GLOBALS['CONFIG']['db_prefix'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    return;
}

$action = $_REQUEST['action'] ?? 'list';
$entity = $_REQUEST['entity'] ?? '';

if (!function_exists('handleList')) {
function handleList(PDO $pdo, string $db_prefix, string $entity): void
{
    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $size = max(1, min(500, (int)($_REQUEST['size'] ?? 25)));

    $query = '';
    $countQuery = '';
    $params = [];

    switch ($entity) {
        case 'users':
            $query = "SELECT u.id, u.username, u.last_name, u.first_name, u.Email AS email, u.phone, u.department, u.can_add, u.can_checkin, d.name AS department_name, a.admin AS is_admin, (SELECT COUNT(*) FROM {$db_prefix}dept_reviewer dr WHERE dr.user_id = u.id) > 0 AS is_reviewer, (SELECT GROUP_CONCAT(dr2.dept_id) FROM {$db_prefix}dept_reviewer dr2 WHERE dr2.user_id = u.id) AS reviewer_depts FROM {$db_prefix}user u LEFT JOIN {$db_prefix}department d ON u.department = d.id LEFT JOIN {$db_prefix}admin a ON u.id = a.id";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}user";
            $orderBy = "u.id";
            break;
        case 'departments':
            $query = "SELECT d.id, d.name, COUNT(u.id) AS user_count FROM {$db_prefix}department d LEFT JOIN {$db_prefix}user u ON u.department = d.id GROUP BY d.id, d.name";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}department";
            $orderBy = "d.id";
            break;
        case 'categories':
            $query = "SELECT c.id, c.name, COUNT(d.id) AS file_count FROM {$db_prefix}category c LEFT JOIN {$db_prefix}data d ON d.category = c.id GROUP BY c.id, c.name";
            $countQuery = "SELECT COUNT(*) FROM {$db_prefix}category";
            $orderBy = "c.id";
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
            return;
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute();
    $total = (int)$countStmt->fetchColumn();

    $last_page = max(1, (int)ceil($total / $size));
    $offset = ($page - 1) * $size;
    $query .= " ORDER BY $orderBy ASC LIMIT $size OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $rows,
        'last_page' => $last_page,
        'last_row' => $total,
    ]);
}
}

if (!function_exists('handleMutation')) {
function handleMutation(PDO $pdo, string $db_prefix, string $action, string $entity, array $data): void
{
    header('Content-Type: application/json');
    ob_start();
    switch ($action) {
        case 'add':
            handleAdd($pdo, $db_prefix, $entity, $data);
            break;
        case 'edit':
            handleEdit($pdo, $db_prefix, $entity, $data);
            break;
        case 'delete':
            handleDelete($pdo, $db_prefix, $entity, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    $output = ob_get_clean();
    $response = json_decode($output, true);
    $tokenData = $GLOBALS['csrf']->getTokenForTemplate(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if (is_array($response)) {
        $response['csrf_token'] = $tokenData['token'];
        $response['csrf_field_name'] = $tokenData['field_name'];
        $response['csrf_index'] = $tokenData['index'];
        $response['csrf_index_name'] = $tokenData['index_name'];
        echo json_encode($response);
    } else {
        echo $output;
    }
}
}

if (!function_exists('handleAdd')) {
function handleAdd(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    switch ($entity) {
        case 'users':
            $username = trim($data['username'] ?? '');
            if ($username === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Username is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}user WHERE username = :username");
            $check->execute([':username' => $username]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already exists']);
                return;
            }
            $password = $data['password'] ?? '';
            if ($GLOBALS['CONFIG']['authen'] === 'mysql' && $password === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Password is required']);
                return;
            }
            if (trim($data['last_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Last name is required']);
                return;
            }
            if (trim($data['first_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'First name is required']);
                return;
            }
            if (trim($data['email'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Email is required']);
                return;
            }
            if ($password === '') {
                $password = makeRandomPassword();
            }
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}user (username, password, department, phone, Email, last_name, first_name, can_add, can_checkin, pw_change_required) VALUES (:username, MD5(:password), :department, :phone, :email, :last_name, :first_name, :can_add, :can_checkin, 1)");
            $stmt->execute([
                ':username' => $username,
                ':password' => $password,
                ':department' => (int)($data['department'] ?? 0),
                ':phone' => $data['phone'] ?? '',
                ':email' => $data['email'] ?? '',
                ':last_name' => $data['last_name'] ?? '',
                ':first_name' => $data['first_name'] ?? '',
                ':can_add' => isset($data['can_add']) ? 1 : 0,
                ':can_checkin' => isset($data['can_checkin']) ? 1 : 0,
            ]);
            $newId = (int)$pdo->lastInsertId();
            $adminStmt = $pdo->prepare("INSERT INTO {$db_prefix}admin (id, admin) VALUES (:id, :admin)");
            $adminStmt->execute([':id' => $newId, ':admin' => isset($data['admin']) ? 1 : 0]);
            if (!empty($data['department_review']) && is_array($data['department_review'])) {
                $revStmt = $pdo->prepare("INSERT INTO {$db_prefix}dept_reviewer (dept_id, user_id) VALUES (:dept_id, :user_id)");
                foreach ($data['department_review'] as $deptId) {
                    $revStmt->execute([':dept_id' => (int)$deptId, ':user_id' => $newId]);
                }
            }
            // Send welcome email via configured mail transport
            if ($GLOBALS['CONFIG']['demo'] !== 'True' && !empty($data['email'])) {
                $adminUser = new User($_SESSION['uid'], $pdo);
                $newUser = new User($newId, $pdo);
                $date = date('Y-m-d H:i:s T');
                $adminFullName = $adminUser->getFullName();
                $adminName = $adminFullName[0] . ' ' . $adminFullName[1];
                $newUserFullName = $newUser->getFullName();
                $mail_from = $adminName . ' <' . $adminUser->getEmailAddress() . '>';
                $mail_headers = "From: " . $mail_from . PHP_EOL;
                $mail_headers .= "Content-Type: text/plain; charset=UTF-8" . PHP_EOL;
                $mail_subject = msg('message_account_created_add_user');
                $mail_body = $newUserFullName . ":" . PHP_EOL . msg('email_i_would_like_to_inform') . PHP_EOL . PHP_EOL;
                $mail_body .= msg('email_your_account_created') . ' ' . $date . '.  ' . msg('email_you_can_now_login') . ':' . PHP_EOL . PHP_EOL;
                $mail_body .= $GLOBALS['CONFIG']['base_url'] . PHP_EOL . PHP_EOL;
                $mail_body .= msg('username') . ': ' . $newUser->getName() . PHP_EOL . PHP_EOL;
                if ($GLOBALS['CONFIG']['authen'] === 'mysql') {
                    $mail_body .= msg('password') . ': ' . $password . PHP_EOL . PHP_EOL;
                }
                $mail_body .= msg('email_salute') . "," . PHP_EOL . $adminName;
                mail($newUser->getEmailAddress(), $mail_subject, $mail_body, $mail_headers);
            }
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        case 'departments':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Department name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE name = :name");
            $check->execute([':name' => $name]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Department already exists']);
                return;
            }
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}department (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        case 'categories':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Category name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}category WHERE name = :name");
            $check->execute([':name' => $name]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Category already exists']);
                return;
            }
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}category (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            $newId = (int)$pdo->lastInsertId();
            if (isset($data['department_permission']) || isset($data['user_permission'])) {
                $catPerms = new CategoryPerms($pdo);
                $perms = [];
                if (isset($data['department_permission'])) {
                    foreach ($data['department_permission'] as $deptId => $rights) {
                        $perms[] = ['dept_id' => (int)$deptId, 'user_id' => null, 'rights' => (int)$rights];
                    }
                }
                if (isset($data['user_permission'])) {
                    foreach ($data['user_permission'] as $userId => $rights) {
                        $perms[] = ['dept_id' => null, 'user_id' => (int)$userId, 'rights' => (int)$rights];
                    }
                }
                $catPerms->saveTemplate($newId, $perms);
            }
            echo json_encode(['success' => true, 'id' => $newId]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}
}

if (!function_exists('handleEdit')) {
function handleEdit(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    $id = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        return;
    }

    switch ($entity) {
        case 'users':
            $username = trim($data['username'] ?? '');
            if ($username === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Username is required']);
                return;
            }
            if (strlen($username) > 255) {
                http_response_code(400);
                echo json_encode(['error' => 'Username too long']);
                return;
            }
            if (trim($data['last_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Last name is required']);
                return;
            }
            if (trim($data['first_name'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'First name is required']);
                return;
            }
            if (trim($data['email'] ?? '') === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Email is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}user WHERE username = :username AND id != :id");
            $check->execute([':username' => $username, ':id' => $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already in use']);
                return;
            }
            $deptId = (int)($data['department'] ?? 0);
            if ($deptId > 0) {
                $deptCheck = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE id = :id");
                $deptCheck->execute([':id' => $deptId]);
                if (!$deptCheck->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid department']);
                    return;
                }
            }
            $stmt = $pdo->prepare("UPDATE {$db_prefix}user SET username = :username, last_name = :last_name, first_name = :first_name, Email = :email, phone = :phone, department = :department, can_add = :can_add, can_checkin = :can_checkin WHERE id = :id");
            $stmt->execute([
                ':username' => $username,
                ':last_name' => $data['last_name'] ?? '',
                ':first_name' => $data['first_name'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':department' => $deptId,
                ':can_add' => isset($data['can_add']) ? 1 : 0,
                ':can_checkin' => isset($data['can_checkin']) ? 1 : 0,
                ':id' => $id,
            ]);
            if (!empty($data['password'])) {
                $pwStmt = $pdo->prepare("UPDATE {$db_prefix}user SET password = MD5(:password) WHERE id = :id");
                $pwStmt->execute([':password' => $data['password'], ':id' => $id]);
            }
            $adminStmt = $pdo->prepare("UPDATE {$db_prefix}admin SET admin = :admin WHERE id = :id");
            $adminStmt->execute([':admin' => isset($data['admin']) ? 1 : 0, ':id' => $id]);
            $pdo->prepare("DELETE FROM {$db_prefix}dept_reviewer WHERE user_id = :id")->execute([':id' => $id]);
            if (!empty($data['department_review']) && is_array($data['department_review'])) {
                $revStmt = $pdo->prepare("INSERT INTO {$db_prefix}dept_reviewer (dept_id, user_id) VALUES (:dept_id, :user_id)");
                foreach ($data['department_review'] as $deptId) {
                    $revStmt->execute([':dept_id' => (int)$deptId, ':user_id' => $id]);
                }
            }
            echo json_encode(['success' => true]);
            return;

        case 'departments':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Department name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE name = :name AND id != :id");
            $check->execute([':name' => $name, ':id' => $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Department name already in use']);
                return;
            }
            $stmt = $pdo->prepare("UPDATE {$db_prefix}department SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            echo json_encode(['success' => true]);
            return;

        case 'categories':
            $name = trim($data['name'] ?? '');
            if ($name === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Category name is required']);
                return;
            }
            $check = $pdo->prepare("SELECT id FROM {$db_prefix}category WHERE name = :name AND id != :id");
            $check->execute([':name' => $name, ':id' => $id]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Category name already in use']);
                return;
            }
            $stmt = $pdo->prepare("UPDATE {$db_prefix}category SET name = :name WHERE id = :id");
            $stmt->execute([':name' => $name, ':id' => $id]);
            if (isset($data['department_permission']) || isset($data['user_permission'])) {
                $catPerms = new CategoryPerms($pdo);
                $perms = [];
                if (isset($data['department_permission'])) {
                    foreach ($data['department_permission'] as $deptId => $rights) {
                        $perms[] = ['dept_id' => (int)$deptId, 'user_id' => null, 'rights' => (int)$rights];
                    }
                }
                if (isset($data['user_permission'])) {
                    foreach ($data['user_permission'] as $userId => $rights) {
                        $perms[] = ['dept_id' => null, 'user_id' => (int)$userId, 'rights' => (int)$rights];
                    }
                }
                $catPerms->saveTemplate($id, $perms);
            }
            echo json_encode(['success' => true]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}
}

if (!function_exists('handleDelete')) {
function handleDelete(PDO $pdo, string $db_prefix, string $entity, array $data): void
{
    $ids = [];
    if (!empty($data['ids']) && is_array($data['ids'])) {
        $ids = array_map('intval', $data['ids']);
    } elseif (!empty($data['id'])) {
        $ids = [(int)$data['id']];
    }
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        return;
    }

    switch ($entity) {
        case 'users':
            foreach ($ids as $id) {
                $pdo->prepare("DELETE FROM {$db_prefix}admin WHERE id = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}user_perms WHERE uid = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}dept_reviewer WHERE user_id = :id")->execute([':id' => $id]);
                $pdo->prepare("UPDATE {$db_prefix}data SET owner = {$GLOBALS['CONFIG']['root_id']} WHERE owner = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}user WHERE id = :id")->execute([':id' => $id]);
            }
            echo json_encode(['success' => true]);
            return;

        case 'departments':
            $assignedId = (int)($data['assigned_id'] ?? 0);
            if ($assignedId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Reassign department ID is required']);
                return;
            }
            $existsCheck = $pdo->prepare("SELECT id FROM {$db_prefix}department WHERE id = :id");
            $existsCheck->execute([':id' => $assignedId]);
            if (!$existsCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Reassign department ID does not exist']);
                return;
            }
            foreach ($ids as $id) {
                if ($id === $assignedId) continue;
                $pdo->prepare("UPDATE {$db_prefix}data SET department = :assigned WHERE department = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
                $pdo->prepare("UPDATE {$db_prefix}user SET department = :assigned WHERE department = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
                $pdo->prepare("UPDATE {$db_prefix}dept_perms SET dept_id = :assigned WHERE dept_id = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
                $pdo->prepare("UPDATE {$db_prefix}dept_reviewer SET dept_id = :assigned WHERE dept_id = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}department WHERE id = :id")->execute([':id' => $id]);
            }
            echo json_encode(['success' => true]);
            return;

        case 'categories':
            $assignedId = (int)($data['assigned_id'] ?? 0);
            if ($assignedId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Reassign category ID is required']);
                return;
            }
            $existsCheck = $pdo->prepare("SELECT id FROM {$db_prefix}category WHERE id = :id");
            $existsCheck->execute([':id' => $assignedId]);
            if (!$existsCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Reassign category ID does not exist']);
                return;
            }
            foreach ($ids as $id) {
                if ($id === $assignedId) continue;
                $pdo->prepare("UPDATE {$db_prefix}data SET category = :assigned WHERE category = :id")->execute([':assigned' => $assignedId, ':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}category_perms WHERE cat_id = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM {$db_prefix}category WHERE id = :id")->execute([':id' => $id]);
            }
            echo json_encode(['success' => true]);
            return;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid entity']);
    }
}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($GLOBALS['CONFIG']['demo'] === 'True') {
        http_response_code(403);
        echo json_encode(['error' => 'Demo mode only, you cannot perform mutations']);
        return;
    }
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF validation failed']);
        return;
    }
    handleMutation($pdo, $db_prefix, $action, $entity, $_POST);
    return;
}

if ($action === 'get_perms' && $entity === 'categories') {
    header('Content-Type: application/json');
    $catId = (int)($_REQUEST['id'] ?? 0);
    if ($catId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category ID']);
        return;
    }
    $catPerms = new CategoryPerms($pdo);
    $rows = $catPerms->getTemplate($catId);
    $deptPerms = [];
    $userPerms = [];
    foreach ($rows as $row) {
        $rights = (int)$row['rights'];
        if ($row['dept_id'] !== null) {
            $deptPerms[(int)$row['dept_id']] = $rights;
        } elseif ($row['user_id'] !== null) {
            $userPerms[(int)$row['user_id']] = $rights;
        }
    }
    echo json_encode(['dept_perms' => $deptPerms, 'user_perms' => $userPerms]);
    return;
}

handleList($pdo, $db_prefix, $entity);
