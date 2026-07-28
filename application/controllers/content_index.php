<?php
session_start();

if (!isset($_SESSION['uid'])) {
    redirect_visitor();
}

$pdo = $GLOBALS['pdo'];
$db_prefix = $GLOBALS['CONFIG']['db_prefix'];

$user_obj = new User($_SESSION['uid'], $pdo);
if (!$user_obj->isAdmin()) {
    header('Location: error?ec=4');
    exit;
}

$result_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rebuild') {
    if (isset($GLOBALS['csrf']) && !$GLOBALS['csrf']->validateToken($_POST)) {
        $result_message = 'Invalid security token. Please try again.';
    } else {
        $stmt = $pdo->query("SELECT id, realname FROM {$db_prefix}data WHERE publishable = 1 ORDER BY id ASC");
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($files);
        $success = 0;
        $failed = 0;

        foreach ($files as $file) {
            $filePath = getFilePath((int)$file['id'], $file['realname'], 'data');
            if (!file_exists($filePath)) {
                $failed++;
                continue;
            }
            $file_mime = File::mime($filePath, $file['realname']);
            if (!TextExtractorFactory::isExtractable($file_mime)) {
                $failed++;
                continue;
            }
            $extractor = TextExtractorFactory::create($file_mime);
            if ($extractor === null) {
                $failed++;
                continue;
            }
            $contentText = $extractor->extract($filePath);
            $deleteStmt = $pdo->prepare("DELETE FROM {$db_prefix}content_index WHERE file_id = :file_id");
            $deleteStmt->execute([':file_id' => $file['id']]);
            if ($contentText !== '') {
                $insertStmt = $pdo->prepare("INSERT INTO {$db_prefix}content_index (file_id, content_text, indexed_at) VALUES (:file_id, :content_text, NOW())");
                $insertStmt->execute([
                    ':file_id' => $file['id'],
                    ':content_text' => $contentText,
                ]);
            }
            $success++;
        }
        $result_message = "Rebuild done: {$success} indexed, {$failed} failed.";
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM {$db_prefix}data WHERE publishable = 1");
$totalFiles = (int)$stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(DISTINCT file_id) FROM {$db_prefix}content_index");
$indexedFiles = (int)$stmt->fetchColumn();
$percent = $totalFiles > 0 ? round($indexedFiles / $totalFiles * 100) : 0;

$csrf_data = $GLOBALS['csrf']->getToken();

draw_header(msg('label_admin'), '');
ob_start();
?>
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('label_content_search_index'); ?></h5></div>
            <div class="card-body">
                <?php if ($result_message !== ''): ?>
                    <div class="alert alert-info"><?php echo htmlentities($result_message); ?></div>
                <?php endif; ?>
                <p><strong><?php echo msg('label_indexed_files'); ?>:</strong> <?php echo $indexedFiles; ?> / <?php echo $totalFiles; ?></p>
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $percent; ?>%</div>
                </div>
                <form method="post" action="content_index" onsubmit="return confirm('<?php echo msg('label_rebuild_confirm'); ?>');">
                    <?php echo $csrf_data['field']; ?>
                    <input type="hidden" name="action" value="rebuild">
                    <button type="submit" class="btn btn-primary"><?php echo msg('label_rebuild_index'); ?></button>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><h5 class="card-title mb-0"><?php echo msg('label_supported_formats'); ?></h5></div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php foreach (TextExtractorFactory::getSupportedFormats() as $name => $mime): ?>
                        <li><?php echo htmlentities($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$GLOBALS['smarty']->assign('content', $content);
display_smarty_template('_content.tpl');
draw_footer();