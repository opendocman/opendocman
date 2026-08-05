# Revision History Log Lifecycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore one revision-log row per actual file version while correctly displaying approved, pending, and rejected revisions.

**Architecture:** Keep the approved log row as `current` while a candidate is staged as the single `incoming` row. Check-in updates or creates that candidate row; approval archives `current` to the next numeric revision and promotes `incoming` to `current`. History derives Pending or Rejected from `data.publishable`, and Details counts only numeric revisions plus `current`.

**Tech Stack:** PHP 7.4+, PDO, MySQL/MariaDB, PHPUnit 9 with Mockery, Playwright/TypeScript

## Global Constraints

- Every actual file version has exactly one row in the revision log.
- Exactly one approved version is marked `current`.
- At most one staged candidate is marked `incoming`.
- Numeric revision values identify archived approved versions.
- Pending and rejected candidates are not counted as approved revisions.
- The check-in revision note remains attached to the file version after approval.
- Authorization and rejection activity stays in the access log and reviewer-comment fields.
- No database schema or translation changes are required; use the existing `message_rejected` key.
- Do not change file staging, checkout, rejection, approval, or access-log behavior outside the log lifecycle.
- Do not commit unless the user explicitly requests it.

## File Structure

- Modify `application/controllers/check-in.php`: preserve `current` and upsert the single `incoming` log row.
- Modify `application/controllers/toBePublished.php`: archive `current` and promote `incoming` without inserting an authorization revision row.
- Modify `application/controllers/history.php`: render the incoming row as Pending or Rejected.
- Modify `application/controllers/details.php`: count approved versions and correctly handle revision `0`.
- Modify `tests/Integration/IncomingRevisionWorkflowTest.php`: specify SQL transitions, row invariants, labels, notes, and counts.
- Modify `tests/incoming-workflow.spec.ts`: verify the complete workflow through the application UI.

---

### Task 1: Preserve Current and Upsert Incoming on Check-In

**Files:**
- Modify: `tests/Integration/IncomingRevisionWorkflowTest.php:196-238,409-426,473-558`
- Modify: `application/controllers/check-in.php:220-234`

**Interfaces:**
- Consumes: `odm_log.id`, `odm_log.revision`, the authenticated username, and `$_POST['note']`.
- Produces: one unchanged `current` row and at most one `incoming` row with the latest author, timestamp, and revision note.

- [ ] **Step 1: Replace the check-in SQL test with failing upsert tests**

Replace `testCheckInInsertsIncomingLogEntry()` with two tests. The first configures the incoming update to affect zero rows and expects an insert:

```php
public function testCheckInCreatesIncomingWithoutChangingCurrent(): void
{
    $fileId = 15;
    $username = 'testuser';
    $note = 'Updated contract terms';

    $userStatement = \Mockery::mock(\PDOStatement::class);
    $userStatement->shouldReceive('execute')->once()->with([':uid' => 1])->andReturn(true);
    $userStatement->shouldReceive('fetch')->once()->andReturn(['username' => $username]);

    $updateStatement = \Mockery::mock(\PDOStatement::class);
    $updateStatement->shouldReceive('execute')->once()->with([
        ':id' => $fileId,
        ':username' => $username,
        ':note' => $note,
    ])->andReturn(true);
    $updateStatement->shouldReceive('rowCount')->once()->andReturn(0);

    $insertStatement = \Mockery::mock(\PDOStatement::class);
    $insertStatement->shouldReceive('execute')->once()->with([
        ':id' => $fileId,
        ':username' => $username,
        ':note' => $note,
    ])->andReturn(true);

    $this->mockPdo->shouldReceive('prepare')
        ->with(\Mockery::pattern("/SELECT username FROM odm_user WHERE id = :uid/"))
        ->once()->ordered()->andReturn($userStatement);
    $this->mockPdo->shouldReceive('prepare')
        ->with(\Mockery::pattern("/UPDATE odm_log SET modified_on = NOW\(\), modified_by = :username, note = :note WHERE id = :id AND revision = 'incoming'/"))
        ->once()->ordered()->andReturn($updateStatement);
    $this->mockPdo->shouldReceive('prepare')
        ->with(\Mockery::pattern("/INSERT INTO odm_log .*'incoming'/i"))
        ->once()->ordered()->andReturn($insertStatement);

    $this->assertTrue($this->simulateCheckInLogUpsert($fileId, $note));
}
```

Add a second test where `rowCount()` returns `1`, and assert that no INSERT is prepared:

```php
public function testReCheckInReplacesExistingIncomingLogEntry(): void
{
    $fileId = 15;
    $username = 'testuser';
    $note = 'Corrected rejected revision';

    $userStatement = \Mockery::mock(\PDOStatement::class);
    $userStatement->shouldReceive('execute')->once()->with([':uid' => 1])->andReturn(true);
    $userStatement->shouldReceive('fetch')->once()->andReturn(['username' => $username]);

    $updateStatement = \Mockery::mock(\PDOStatement::class);
    $updateStatement->shouldReceive('execute')->once()->with([
        ':id' => $fileId,
        ':username' => $username,
        ':note' => $note,
    ])->andReturn(true);
    $updateStatement->shouldReceive('rowCount')->once()->andReturn(1);

    $this->mockPdo->shouldReceive('prepare')->once()->ordered()->andReturn($userStatement);
    $this->mockPdo->shouldReceive('prepare')->once()->ordered()->andReturn($updateStatement);

    $this->assertTrue($this->simulateCheckInLogUpsert($fileId, $note));
}
```

Update the test helper to model the intended update-then-insert behavior:

```php
private function simulateCheckInLogUpsert(int $fileId, string $note): bool
{
    $stmt = $this->mockPdo->prepare(
        "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid"
    );
    $stmt->execute([':uid' => $_SESSION['uid']]);
    $username = $stmt->fetch()['username'];

    $stmt = $this->mockPdo->prepare(
        "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET modified_on = NOW(), modified_by = :username, note = :note WHERE id = :id AND revision = 'incoming'"
    );
    $params = [':id' => $fileId, ':username' => $username, ':note' => $note];
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $stmt = $this->mockPdo->prepare(
            "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'incoming')"
        );
        $stmt->execute($params);
    }

    return true;
}
```

Add a controller regression assertion so this task tests production code rather than only the SQL simulation:

```php
public function testCheckInControllerPreservesCurrentAndUpsertsIncoming(): void
{
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/application/controllers/check-in.php'
    );

    $this->assertStringNotContainsString("SET revision = 'pending'", $source);
    $this->assertStringContainsString(
        "WHERE id = :id AND revision = 'incoming'",
        $source
    );
    $this->assertStringContainsString('if ($stmt->rowCount() === 0)', $source);
}
```

- [ ] **Step 2: Run the focused tests and verify failure**

Run:

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Integration/IncomingRevisionWorkflowTest.php --filter 'test(CheckInCreatesIncomingWithoutChangingCurrent|ReCheckInReplacesExistingIncomingLogEntry|CheckInControllerPreservesCurrentAndUpsertsIncoming)'
```

Expected: FAIL because `check-in.php` still changes `current` to `pending` and always inserts an incoming row.

- [ ] **Step 3: Implement the check-in log upsert**

In `application/controllers/check-in.php`, remove the query that changes `current` to `pending`. Replace the unconditional incoming INSERT with:

```php
$params = array(
    ':id' => $id,
    ':username' => $username,
    ':note' => $_POST['note']
);

$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET modified_on = NOW(), modified_by = :username, note = :note WHERE id = :id AND revision = 'incoming'";
$stmt = $pdo->prepare($query);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'incoming')";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
}
```

Do not update any row whose revision is `current`.

- [ ] **Step 4: Run the focused tests**

Run the command from Step 2.

Expected: PASS.

- [ ] **Step 5: Run PHP syntax validation**

Run:

```bash
php -l application/controllers/check-in.php
php -l tests/Integration/IncomingRevisionWorkflowTest.php
```

Expected: both report `No syntax errors detected`.

- [ ] **Step 6: Commit only if explicitly authorized**

```bash
git add application/controllers/check-in.php tests/Integration/IncomingRevisionWorkflowTest.php
git commit -m "fix: preserve approved revision during check-in"
```

---

### Task 2: Promote Exactly One Revision Row on Approval

**Files:**
- Modify: `tests/Integration/IncomingRevisionWorkflowTest.php:121-194,382-407,473-558`
- Modify: `application/controllers/toBePublished.php:272-310`

**Interfaces:**
- Consumes: one `current` row, one `incoming` row, and the next numeric revision from the existing `MAX(...)+1` query.
- Produces: the old `current` row as numeric revision `N` and the `incoming` row as the new `current`; no new log row.

- [ ] **Step 1: Rewrite the approval test to require two updates and no insert**

Rename `testApprovalFlowUpdatesIncomingLogAndInsertsCurrent()` to `testApprovalArchivesCurrentAndPromotesIncoming()`.

After the existing count statement mock, create separate mocks:

```php
$archiveStatement = \Mockery::mock(\PDOStatement::class);
$archiveStatement->shouldReceive('execute')->once()->with([
    ':rev' => $revisionCount,
    ':id' => $fileId,
])->andReturn(true);

$promoteStatement = \Mockery::mock(\PDOStatement::class);
$promoteStatement->shouldReceive('execute')->once()->with([
    ':id' => $fileId,
])->andReturn(true);
```

Require these SQL statements in order after the count query:

```php
$this->mockPdo->shouldReceive('prepare')
    ->with(\Mockery::pattern("/UPDATE odm_log SET revision = :rev WHERE id = :id AND revision = 'current'/"))
    ->once()->ordered()->andReturn($archiveStatement);
$this->mockPdo->shouldReceive('prepare')
    ->with(\Mockery::pattern("/UPDATE odm_log SET revision = 'current' WHERE id = :id AND revision = 'incoming'/"))
    ->once()->ordered()->andReturn($promoteStatement);
```

Remove the reviewer username and INSERT expectations. Update `simulateApprovalFlow()` to run the same count and two UPDATE statements, without querying the reviewer username or inserting a row.

- [ ] **Step 2: Rewrite the full lifecycle test around one row per version**

Update `testFullWorkflowSequence()` so its in-memory transitions are:

```php
$log = [
    ['revision' => 'current', 'note' => 'Initial import'],
];

$log[] = ['revision' => 'incoming', 'note' => 'First revision'];
$this->assertCount(2, $log);
$this->assertSame(1, $this->countCurrent($log));
$this->assertSame(1, $this->countIncoming($log));

$revisionCount = $this->countRevisionsInLog($log);
foreach ($log as &$entry) {
    if ($entry['revision'] === 'current') {
        $entry['revision'] = (string) $revisionCount;
    } elseif ($entry['revision'] === 'incoming') {
        $entry['revision'] = 'current';
    }
}
unset($entry);

$this->assertCount(2, $log);
$this->assertSame(['0', 'current'], array_column($log, 'revision'));
$this->assertSame(['Initial import', 'First revision'], array_column($log, 'note'));

$log[] = ['revision' => 'incoming', 'note' => 'Second revision'];
$revisionCount = $this->countRevisionsInLog($log);
foreach ($log as &$entry) {
    if ($entry['revision'] === 'current') {
        $entry['revision'] = (string) $revisionCount;
    } elseif ($entry['revision'] === 'incoming') {
        $entry['revision'] = 'current';
    }
}
unset($entry);

$this->assertCount(3, $log);
$this->assertSame(['0', '1', 'current'], array_column($log, 'revision'));
$this->assertSame(['Initial import', 'First revision', 'Second revision'], array_column($log, 'note'));
```

Add:

```php
private function countIncoming(array $log): int
{
    return count(array_filter($log, fn($entry) => $entry['revision'] === 'incoming'));
}
```

Add a production-controller regression assertion:

```php
public function testApprovalControllerArchivesCurrentAndPromotesIncoming(): void
{
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/application/controllers/toBePublished.php'
    );

    $this->assertStringContainsString(
        "revision = :rev WHERE id = :id AND revision = 'current'",
        $source
    );
    $this->assertStringContainsString(
        "revision = 'current' WHERE id = :id AND revision = 'incoming'",
        $source
    );
    $this->assertStringNotContainsString(
        "revision IN ('pending', 'incoming')",
        $source
    );
    $this->assertStringNotContainsString(
        "VALUES(:id, NOW(), :username, :note, 'current')",
        $source
    );
}
```

- [ ] **Step 3: Run the focused tests and verify failure**

Run:

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Integration/IncomingRevisionWorkflowTest.php --filter 'test(ApprovalArchivesCurrentAndPromotesIncoming|ApprovalControllerArchivesCurrentAndPromotesIncoming|FullWorkflowSequence)'
```

Expected: FAIL because approval currently assigns the same numeric revision to both marker rows and inserts a third current row.

- [ ] **Step 4: Implement the two approval transitions**

In `application/controllers/toBePublished.php`, retain the existing next-revision query. Replace the bulk marker update and current INSERT with:

```php
$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = :rev WHERE id = :id AND revision = 'current'";
$stmt = $pdo->prepare($query);
$stmt->execute([':rev' => $revisionCount, ':id' => $fileid]);

$query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = 'current' WHERE id = :id AND revision = 'incoming'";
$stmt = $pdo->prepare($query);
$stmt->execute([':id' => $fileid]);
```

Remove the reviewer username query used only by the deleted revision INSERT. Keep `$file_obj->setReviewerComments(...)`, `$file_obj->Publishable(1)`, and `AccessLog::addLogEntry(..., 'Y', ...)` unchanged.

- [ ] **Step 5: Run the focused tests**

Run the command from Step 3.

Expected: PASS with two rows after the first approval and three rows after the second approval.

- [ ] **Step 6: Run PHP syntax validation**

```bash
php -l application/controllers/toBePublished.php
php -l tests/Integration/IncomingRevisionWorkflowTest.php
```

Expected: both pass.

- [ ] **Step 7: Commit only if explicitly authorized**

```bash
git add application/controllers/toBePublished.php tests/Integration/IncomingRevisionWorkflowTest.php
git commit -m "fix: promote one revision log row on approval"
```

---

### Task 3: Render Pending and Rejected Without Inflating Details Count

**Files:**
- Modify: `tests/Integration/IncomingRevisionWorkflowTest.php:251-264,301-351,428-461`
- Modify: `application/controllers/history.php:55-63,229-269`
- Modify: `application/controllers/details.php:129-187`

**Interfaces:**
- Consumes: `FileData::isPublishable()` and rows containing `revision` values `current`, `incoming`, or numeric strings.
- Produces: Latest, Pending, Rejected, and numeric labels plus an approved-version count excluding `incoming`.

- [ ] **Step 1: Add failing history label tests**

Change the history simulation helper signature to:

```php
private function simulateHistoryDisplayForRevision(string $revision, int $publishable = 1): string
```

Render incoming state with existing language text semantics:

```php
if ($revision === 'current') {
    echo '<td class="text-center"><a href="details?id=1&state=1"><span class="revision">Latest</span></a>';
} elseif ($revision === 'incoming') {
    echo '<td>' . ($publishable === -1 ? 'Rejected' : 'Pending');
}
```

Retain `testHistoryPageDisplaysIncomingAsPendingWithoutLink()` and add:

```php
public function testHistoryPageDisplaysIncomingAsRejectedWithoutLink(): void
{
    $result = $this->simulateHistoryDisplayForRevision('incoming', -1);

    $this->assertStringContainsString('Rejected', $result);
    $this->assertStringNotContainsString('<a href', $result);
}
```

- [ ] **Step 2: Add failing approved-version count tests**

Add a helper:

```php
private function countApprovedVersions(array $rows): int
{
    return count(array_filter($rows, function (array $row): bool {
        return $row['revision'] === 'current' || is_numeric($row['revision']);
    }));
}
```

Add:

```php
public function testDetailsCountExcludesPendingOrRejectedIncomingRevision(): void
{
    $rows = [
        ['revision' => '0'],
        ['revision' => 'current'],
        ['revision' => 'incoming'],
    ];

    $this->assertSame(2, $this->countApprovedVersions($rows));
}

public function testDetailsCountIncludesEachApprovedVersionOnce(): void
{
    $rows = [
        ['revision' => '0'],
        ['revision' => '1'],
        ['revision' => 'current'],
    ];

    $this->assertSame(3, $this->countApprovedVersions($rows));
}
```

Add production-controller assertions:

```php
public function testHistoryControllerDerivesRejectedLabelFromPublishable(): void
{
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/application/controllers/history.php'
    );

    $this->assertStringContainsString('$publishable = $datafile->isPublishable()', $source);
    $this->assertStringContainsString("msg('message_rejected')", $source);
}

public function testDetailsControllerCountsApprovedRowsInsteadOfAllLogRows(): void
{
    $source = file_get_contents(
        dirname(__DIR__, 2) . '/application/controllers/details.php'
    );

    $this->assertStringContainsString('if (isset($revision_id))', $source);
    $this->assertStringContainsString('array_filter($revisionData', $source);
    $this->assertStringNotContainsString('$rows = $stmt->rowCount()', $source);
}
```

- [ ] **Step 3: Run the focused tests and verify failure**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Integration/IncomingRevisionWorkflowTest.php --filter 'test(HistoryPageDisplaysIncomingAsRejectedWithoutLink|HistoryControllerDerivesRejectedLabelFromPublishable|DetailsCount|DetailsControllerCountsApprovedRowsInsteadOfAllLogRows)'
```

Expected: FAIL until History uses `publishable` for the incoming label and Details stops using raw row count.

- [ ] **Step 4: Render rejected incoming rows in History**

In `application/controllers/history.php`, capture publishable state with the other `FileData` fields:

```php
$publishable = $datafile->isPublishable();
```

Remove the obsolete `pending` skip block. Keep legacy `pending` rendering as Pending, but render `incoming` separately:

```php
} elseif ($revision === 'incoming') {
    $label = $publishable == -1 ? msg('message_rejected') : msg('historypage_pending');
    echo '<td>' . e::h($label) . e::h($extra_message);
} elseif ($revision === 'pending') {
    echo '<td>' . e::h(msg('historypage_pending')) . e::h($extra_message);
```

Do not add a language key; `message_rejected` already exists in all 17 language files.

- [ ] **Step 5: Count approved rows in Details and handle revision zero**

In `application/controllers/details.php`, change:

```php
if (!empty($revision_id)) {
```

to:

```php
if (isset($revision_id)) {
```

After fetching `$revisionData`, replace `$stmt->rowCount()` and the existing row-count condition with:

```php
if (isset($revision_id)) {
    $revision = $revision_id + 1;
} else {
    $approvedRows = array_filter($revisionData, function (array $row): bool {
        return $row['revision'] === 'current' || is_numeric($row['revision']);
    });
    $revision = (string) count($approvedRows);
}
```

This makes `details?id=51_0` query revision zero and makes current Details ignore the incoming candidate.

- [ ] **Step 6: Run focused tests and syntax validation**

```bash
php application/vendor/bin/phpunit -c phpunit.xml.dist tests/Integration/IncomingRevisionWorkflowTest.php --filter 'test(HistoryPage|DetailsCount)'
php -l application/controllers/history.php
php -l application/controllers/details.php
php -l tests/Integration/IncomingRevisionWorkflowTest.php
```

Expected: all pass.

- [ ] **Step 7: Commit only if explicitly authorized**

```bash
git add application/controllers/history.php application/controllers/details.php tests/Integration/IncomingRevisionWorkflowTest.php
git commit -m "fix: display revision states and approved counts"
```

---

### Task 4: Verify the Complete Browser Workflow

**Files:**
- Modify: `tests/incoming-workflow.spec.ts:106-224`

**Interfaces:**
- Consumes: History table rows, `#details_revision`, check-in notes, and approval/rejection actions.
- Produces: end-to-end proof that notes and version labels remain correct through pending, approval, and rejection.

- [ ] **Step 1: Add failing pending-state assertions after check-in**

At the end of test 3, after `waitForMessage(page, 'checked in')`, add:

```ts
await retryGoto(page, '/history?id=' + fileId);
const pendingRows = page.locator('table.table-striped tbody tr');
await expect(pendingRows).toHaveCount(2);
await expect(pendingRows.filter({ hasText: 'Latest' })).toContainText('Initial import');
await expect(pendingRows.filter({ hasText: 'Pending' })).toContainText('Updated via E2E test');

await retryGoto(page, '/details?id=' + fileId);
await expect(page.locator('#details_revision')).toHaveText('1');
```

- [ ] **Step 2: Strengthen post-approval assertions**

Replace the loose `count >= 2` assertion in test 4 with:

```ts
const revisionRows = page.locator('table.table-striped tbody tr');
await expect(revisionRows).toHaveCount(2);
await expect(revisionRows.filter({ hasText: '1' })).toContainText('Initial import');
await expect(revisionRows.filter({ hasText: 'Latest' })).toContainText('Updated via E2E test');
await expect(revisionRows).not.toContainText('Approved revision via E2E');

await retryGoto(page, '/details?id=' + fileId);
await expect(page.locator('#details_revision')).toHaveText('2');
```

Use a more specific Version 1 locator if `hasText: '1'` also matches a date in the test environment:

```ts
const versionOneRow = revisionRows.filter({ has: page.locator('.revision', { hasText: /^1$/ }) });
```

- [ ] **Step 3: Add rejected-state assertions**

At the end of test 5, after the rejection success message, add:

```ts
await retryGoto(page, '/history?id=' + fileId);
const rejectedRows = page.locator('table.table-striped tbody tr');
await expect(rejectedRows).toHaveCount(3);
await expect(rejectedRows.filter({ hasText: 'Latest' })).toContainText('Updated via E2E test');
await expect(rejectedRows.filter({ hasText: 'Rejected' })).toContainText('Attempted fix via E2E');

await retryGoto(page, '/details?id=' + fileId);
await expect(page.locator('#details_revision')).toHaveText('2');
```

- [ ] **Step 4: Run the E2E file and verify failure before implementation**

With the application available at `http://localhost:8080`, run:

```bash
npx playwright test tests/incoming-workflow.spec.ts
```

Expected before Tasks 1-3: FAIL on missing Latest during pending, duplicate Version 1 rows after approval, or missing Rejected label.

- [ ] **Step 5: Run the focused E2E workflow after implementation**

Run the same command.

Expected: all incoming workflow tests pass.

- [ ] **Step 6: Commit only if explicitly authorized**

```bash
git add tests/incoming-workflow.spec.ts
git commit -m "test: cover revision history lifecycle"
```

---

### Task 5: Full Regression Verification

**Files:**
- Verify only; no planned file changes.

**Interfaces:**
- Consumes: completed controller and test changes.
- Produces: evidence that the revision fix does not break the broader application.

- [ ] **Step 1: Run all PHPUnit tests**

```bash
make test
```

Expected: all unit and integration tests pass.

- [ ] **Step 2: Run the project E2E suite**

With the application running at `http://localhost:8080`:

```bash
npm run test:e2e
```

Expected: all Playwright tests pass.

- [ ] **Step 3: Validate syntax of every changed PHP file**

```bash
php -l application/controllers/check-in.php
php -l application/controllers/toBePublished.php
php -l application/controllers/history.php
php -l application/controllers/details.php
php -l tests/Integration/IncomingRevisionWorkflowTest.php
```

Expected: every file reports `No syntax errors detected`.

- [ ] **Step 4: Inspect the final diff**

```bash
git diff --check
git diff -- application/controllers/check-in.php application/controllers/toBePublished.php application/controllers/history.php application/controllers/details.php tests/Integration/IncomingRevisionWorkflowTest.php tests/incoming-workflow.spec.ts
```

Expected: no whitespace errors; only the approved revision-log lifecycle and its tests changed.

- [ ] **Step 5: Commit only if explicitly authorized**

```bash
git add application/controllers/check-in.php application/controllers/toBePublished.php application/controllers/history.php application/controllers/details.php tests/Integration/IncomingRevisionWorkflowTest.php tests/incoming-workflow.spec.ts
git commit -m "fix: correct revision history lifecycle"
```
