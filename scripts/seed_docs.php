<?php
/**
 * Seed 5000 test documents for performance testing.
 * Run: docker cp seed_docs.php opendocman-app-1:/tmp/ && docker exec opendocman-app-1 php /tmp/seed_docs.php
 */

$pdo = new PDO(
    'mysql:host=db;dbname=opendocman;charset=utf8',
    'opendocman',
    'cWzzQzOySoBvoO84gJykRedP'
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$total = 5000;
$batch = 500;
$prefix = 'odm_';
$uid = 1; // admin
$dept_id = 1;

echo "Starting seed of $total documents...\n";

$extensions = ['pdf', 'docx', 'xlsx', 'pptx', 'txt', 'jpg', 'png', 'csv'];
$descriptions = [
    'Monthly report', 'Q4 financials', 'Employee handbook', 'Project plan',
    'Meeting notes', 'Technical spec', 'Budget overview', 'Training material',
    'Annual review', 'Contract draft', 'Policy update', 'Archived record'
];
$adjectives = ['Draft', 'Final', 'Revised', 'Approved', 'Pending', 'Archive', 'Backup', 'Master', 'Working', 'Copy'];

$startTime = microtime(true);

for ($batchStart = 0; $batchStart < $total; $batchStart += $batch) {
    $pdo->beginTransaction();

    $batchEnd = min($batchStart + $batch, $total);

    // Bulk INSERT into odm_data
    $dataValues = [];
    $dataParams = [];

    for ($i = $batchStart; $i < $batchEnd; $i++) {
        $ext = $extensions[array_rand($extensions)];
        $adj = $adjectives[array_rand($adjectives)];
        $desc = $descriptions[array_rand($descriptions)];
        $realname = "doc_{$i}_{$adj}.{$ext}";
        $created = date('Y-m-d H:i:s', strtotime("-" . rand(0, 365) . " days"));
        $description = "{$desc} #{$i}";
        $category = rand(1, 12);
        $owner = 1;
        $status = 0;
        $department = 1;
        $publishable = 1;
        $default_rights = 0;

        $dataValues[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)";
        array_push($dataParams, $category, $owner, $realname, $created, $description, $status, $department, $publishable, $default_rights);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}data (category, owner, realname, created, description, status, department, publishable, default_rights) "
        . "VALUES " . implode(',', $dataValues)
    );
    $stmt->execute($dataParams);

    $firstId = (int) $pdo->lastInsertId();
    $lastId = $firstId + ($batchEnd - $batchStart) - 1;

    // Bulk INSERT into odm_user_perms (admin rights = 4)
    $upValues = [];
    $upParams = [];
    for ($fid = $firstId; $fid <= $lastId; $fid++) {
        $upValues[] = "(?, ?, ?)";
        array_push($upParams, $fid, $uid, 4);
    }
    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}user_perms (fid, uid, rights) VALUES " . implode(',', $upValues)
    );
    $stmt->execute($upParams);

    // Bulk INSERT into odm_dept_perms (view right = 1)
    $dpValues = [];
    $dpParams = [];
    for ($fid = $firstId; $fid <= $lastId; $fid++) {
        $dpValues[] = "(?, ?, ?)";
        array_push($dpParams, $fid, $dept_id, 1);
    }
    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}dept_perms (fid, dept_id, rights) VALUES " . implode(',', $dpValues)
    );
    $stmt->execute($dpParams);

    // Bulk INSERT into odm_log (modified dates)
    $logValues = [];
    $logParams = [];
    for ($fid = $firstId; $fid <= $lastId; $fid++) {
        $modified = date('Y-m-d H:i:s', strtotime("-" . rand(0, 30) . " days"));
        $logValues[] = "(?, ?)";
        array_push($logParams, $fid, $modified);
    }
    $stmt = $pdo->prepare(
        "INSERT INTO {$prefix}log (id, modified_on) VALUES " . implode(',', $logValues)
    );
    $stmt->execute($logParams);

    $pdo->commit();

    $elapsed = round(microtime(true) - $startTime, 2);
    echo "  Inserted " . ($batchEnd) . "/{$total} (elapsed: {$elapsed}s)\n";
}

$count = $pdo->query("SELECT COUNT(*) FROM {$prefix}data")->fetchColumn();
echo "Done. Total documents: {$count}\n";
echo "Total time: " . round(microtime(true) - $startTime, 2) . "s\n";
