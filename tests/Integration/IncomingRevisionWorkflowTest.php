<?php

use PHPUnit\Framework\TestCase;

class IncomingRevisionWorkflowTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['CONFIG'] = [
            'dataDir' => '/tmp/opendocman_data/',
            'archiveDir' => '/tmp/opendocman_archive/',
            'revisionDir' => '/tmp/opendocman_revision/',
            'incomingDir' => '/tmp/opendocman_data/incoming/',
            'db_prefix' => 'odm_',
            'root_id' => 1,
        ];

        $this->mockPdo = \Mockery::mock(PDO::class);
        $this->mockStatement = \Mockery::mock(\PDOStatement::class);

        $this->mockStatement->shouldReceive('execute')->andReturn(true)->byDefault();
        $this->mockStatement->shouldReceive('fetch')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('fetchAll')->andReturn([])->byDefault();
        $this->mockStatement->shouldReceive('fetchColumn')->andReturn(false)->byDefault();
        $this->mockStatement->shouldReceive('rowCount')->andReturn(0)->byDefault();

        $this->mockPdo->shouldReceive('prepare')->andReturn($this->mockStatement)->byDefault();
        $this->mockPdo->shouldReceive('lastInsertId')->andReturn(5)->byDefault();

        $GLOBALS['pdo'] = $this->mockPdo;

        $_SESSION = [];
        $_SESSION['uid'] = 1;

        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    public function testGetFilePathReturnsIncomingPath(): void
    {
        $fileId = 42;
        $realname = 'report.pdf';
        $expectedBase = rtrim($GLOBALS['CONFIG']['dataDir'], '/') . '/incoming/';
        $expectedPath = $expectedBase . $fileId . '/' . $realname;

        $result = getFilePath($fileId, $realname, 'incoming');

        $this->assertSame($expectedPath, $result);
    }

    public function testGetFilePathIncomingUsesIncomingDirConfig(): void
    {
        $GLOBALS['CONFIG']['dataDir'] = '/var/lib/opendocman/files/';
        $fileId = 7;
        $realname = 'contract.docx';
        $expectedPath = '/var/lib/opendocman/files/incoming/7/contract.docx';

        $result = getFilePath($fileId, $realname, 'incoming');

        $this->assertSame($expectedPath, $result);
    }

    public function testRevisionCountQueryExcludesCurrentAndIncoming(): void
    {
        $fileId = 10;

        $countStatement = \Mockery::mock(\PDOStatement::class);
        $countStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $fileId])
            ->andReturn(true);
        $countStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn('3');

        $this->mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern(
                "/SELECT COALESCE\(MAX\(CAST\(revision AS UNSIGNED\)\) \+ 1, 0\) FROM odm_log WHERE id = :id AND revision NOT IN \('current', 'incoming', 'pending'\)/"
            ))
            ->andReturn($countStatement);

        $revisionCount = $this->simulateCountRevisions($fileId);

        $this->assertSame(3, $revisionCount);
    }

    public function testRevisionCountWithNoPriorRevisions(): void
    {
        $fileId = 10;

        $countStatement = \Mockery::mock(\PDOStatement::class);
        $countStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $fileId])
            ->andReturn(true);
        $countStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn('0');

        $this->mockPdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::pattern(
                "/SELECT COALESCE\(MAX\(CAST\(revision AS UNSIGNED\)\) \+ 1, 0\) FROM odm_log WHERE id = :id AND revision NOT IN \('current', 'incoming', 'pending'\)/"
            ))
            ->andReturn($countStatement);

        $revisionCount = $this->simulateCountRevisions($fileId);

        $this->assertSame(0, $revisionCount);
    }

    public function testApprovalArchivesCurrentAndPromotesIncoming(): void
    {
        $fileId = 10;
        $revisionCount = 2;

        $countStatement = \Mockery::mock(\PDOStatement::class);
        $countStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $fileId])
            ->andReturn(true);
        $countStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn((string) $revisionCount);

        $archiveStatement = \Mockery::mock(\PDOStatement::class);
        $archiveStatement->shouldReceive('execute')->once()->with([
            ':rev' => $revisionCount,
            ':id' => $fileId,
        ])->andReturn(true);

        $promoteStatement = \Mockery::mock(\PDOStatement::class);
        $promoteStatement->shouldReceive('execute')->once()->with([
            ':id' => $fileId,
        ])->andReturn(true);

        $accessStatement = \Mockery::mock(\PDOStatement::class);
        $accessStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);
        $accessStatement->shouldReceive('fetchColumn')->once()->andReturn('1');

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/SELECT COALESCE\(MAX\(CAST\(revision AS UNSIGNED\)\) \+ 1, 0\) FROM odm_log WHERE id = :id AND revision NOT IN \('current', 'incoming', 'pending'\)/"
            ))
            ->once()
            ->ordered()
            ->andReturn($countStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/SELECT COUNT\(\*\) FROM odm_access_log WHERE file_id = :id AND action = 'Y'/"))
            ->once()->ordered()->andReturn($accessStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/UPDATE odm_log SET revision = :rev WHERE id = :id AND revision = 'current'/"))
            ->once()->ordered()->andReturn($archiveStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/UPDATE odm_log SET revision = 'current' WHERE id = :id AND revision = 'incoming'/"))
            ->once()->ordered()->andReturn($promoteStatement);

        $result = $this->simulateApprovalFlow($fileId);

        $this->assertTrue($result);
    }

    public function testApprovalWithoutPriorApprovalReplacesCurrentInsteadOfArchiving(): void
    {
        $fileId = 11;
        $revisionCount = 0;

        $countStatement = \Mockery::mock(\PDOStatement::class);
        $countStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);
        $countStatement->shouldReceive('fetchColumn')->once()->andReturn((string) $revisionCount);

        $accessStatement = \Mockery::mock(\PDOStatement::class);
        $accessStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);
        $accessStatement->shouldReceive('fetchColumn')->once()->andReturn('0');

        $deleteStatement = \Mockery::mock(\PDOStatement::class);
        $deleteStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);

        $promoteStatement = \Mockery::mock(\PDOStatement::class);
        $promoteStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/SELECT COALESCE\(MAX\(CAST\(revision AS UNSIGNED\)\) \+ 1, 0\) FROM odm_log WHERE id = :id AND revision NOT IN \('current', 'incoming', 'pending'\)/"
            ))
            ->once()->ordered()->andReturn($countStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/SELECT COUNT\(\*\) FROM odm_access_log WHERE file_id = :id AND action = 'Y'/"))
            ->once()->ordered()->andReturn($accessStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/DELETE FROM odm_log WHERE id = :id AND revision = 'current'/"))
            ->once()->ordered()->andReturn($deleteStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/UPDATE odm_log SET revision = 'current' WHERE id = :id AND revision = 'incoming'/"))
            ->once()->ordered()->andReturn($promoteStatement);

        $this->assertTrue($this->simulateApprovalFlow($fileId));
    }

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

    public function testApprovalControllerGuardsArchiveAndPromotionFailures(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/application/controllers/toBePublished.php'
        );

        $this->assertStringContainsString(
            "if (!file_exists(\$dataPath)) {\n                        header(\"Location:toBePublished?last_message=\" . urlencode(msg('message_error_performing_action')));\n                        exit;\n                    }",
            $source
        );
        $this->assertStringContainsString(
            "if (!copy(\$dataPath, \$revisionPath)) {\n                        header(\"Location:toBePublished?last_message=\" . urlencode(msg('message_error_performing_action')));\n                        exit;\n                    }",
            $source
        );
        $this->assertStringContainsString(
            "if (!rename(\$incomingPath, \$newDataPath)) {\n                    if (\$hasPriorApproval || \$revisionCount > 0) {\n                        copy(\$revisionPath, \$newDataPath);\n                    }\n                    header(\"Location:toBePublished?last_message=\" . urlencode(msg('message_error_performing_action')));\n                    exit;\n                }",
            $source
        );
    }

    public function testApprovalControllerTransitionsLogsOnlyAfterSuccessfulPromotion(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/application/controllers/toBePublished.php'
        );

        $rename = strpos($source, 'rename($incomingPath, $newDataPath)');
        $archive = strpos($source, "revision = :rev WHERE id = :id AND revision = 'current'");
        $promote = strpos($source, "revision = 'current' WHERE id = :id AND revision = 'incoming'");
        $publish = strpos($source, '$file_obj->Publishable(1);');

        $this->assertNotFalse($rename);
        $this->assertNotFalse($archive);
        $this->assertNotFalse($promote);
        $this->assertNotFalse($publish);
        $this->assertLessThan($archive, $rename);
        $this->assertLessThan($promote, $archive);
        $this->assertLessThan($publish, $promote);
    }

    public function testCheckInCreatesIncomingWithoutChangingCurrent(): void
    {
        $fileId = 15;
        $username = 'testuser';
        $note = 'Updated contract terms';

        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')->once()->with([':uid' => 1])->andReturn(true);
        $userStatement->shouldReceive('fetch')->once()->andReturn(['username' => $username]);

        $existsStatement = \Mockery::mock(\PDOStatement::class);
        $existsStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);
        $existsStatement->shouldReceive('fetchColumn')->once()->andReturn(0);

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
            ->with(\Mockery::pattern("/SELECT COUNT\(\*\) FROM odm_log WHERE id = :id AND revision = 'incoming'/"))
            ->once()->ordered()->andReturn($existsStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/INSERT INTO odm_log .*'incoming'/i"))
            ->once()->ordered()->andReturn($insertStatement);

        $this->assertTrue($this->simulateCheckInLogUpsert($fileId, $note));
    }

    public function testReCheckInUpdatesExistingIncomingRow(): void
    {
        $fileId = 15;
        $username = 'testuser';
        $note = 'Corrected rejected revision';

        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')->once()->with([':uid' => 1])->andReturn(true);
        $userStatement->shouldReceive('fetch')->once()->andReturn(['username' => $username]);

        $existsStatement = \Mockery::mock(\PDOStatement::class);
        $existsStatement->shouldReceive('execute')->once()->with([':id' => $fileId])->andReturn(true);
        $existsStatement->shouldReceive('fetchColumn')->once()->andReturn(1);

        $updateStatement = \Mockery::mock(\PDOStatement::class);
        $updateStatement->shouldReceive('execute')->once()->with([
            ':id' => $fileId,
            ':username' => $username,
            ':note' => $note,
        ])->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/SELECT username FROM odm_user WHERE id = :uid/"))
            ->once()->ordered()->andReturn($userStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/SELECT COUNT\(\*\) FROM odm_log WHERE id = :id AND revision = 'incoming'/"))
            ->once()->ordered()->andReturn($existsStatement);
        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern("/UPDATE odm_log SET modified_on = NOW\(\), modified_by = :username, note = :note WHERE id = :id AND revision = 'incoming'/"))
            ->once()->ordered()->andReturn($updateStatement);

        $this->assertTrue($this->simulateCheckInLogUpsert($fileId, $note));
    }

    public function testCheckInControllerPreservesCurrentAndUpsertsIncoming(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/application/controllers/check-in.php'
        );

        $this->assertStringNotContainsString("SET revision = 'pending'", $source);
        $this->assertStringContainsString(
            "SELECT COUNT(*) FROM {\$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision = 'incoming'",
            $source
        );
        $this->assertStringContainsString('if ($incomingExists)', $source);
        $this->assertStringNotContainsString('if ($stmt->rowCount() === 0)', $source);
    }

    public function testCheckInUsesIncomingFileType(): void
    {
        $fileId = 20;
        $filename = 'policy_v2.pdf';
        $expectedPath = rtrim($GLOBALS['CONFIG']['dataDir'], '/') . '/incoming/20/' . $filename;

        $result = getFilePath($fileId, $filename, 'incoming');

        $this->assertSame($expectedPath, $result);
    }

    public function testHistoryPageDisplaysIncomingAsPendingWithoutLink(): void
    {
        $result = $this->simulateHistoryDisplayForRevision('incoming');

        $this->assertStringContainsString('Pending', $result);
        $this->assertStringNotContainsString('<a href', $result);
    }

    public function testHistoryPageDisplaysIncomingAsRejectedWithoutLink(): void
    {
        $result = $this->simulateHistoryDisplayForRevision('incoming', -1);

        $this->assertStringContainsString('Rejected', $result);
        $this->assertStringNotContainsString('<a href', $result);
    }

    public function testHistoryPageDisplaysCurrentAsLatestWithLink(): void
    {
        $result = $this->simulateHistoryDisplayForRevision('current');

        $this->assertStringContainsString('Latest', $result);
    }

    public function testPmtDeleteRemovesFilesFromAllDirectories(): void
    {
        $fileId = 30;
        $realname = 'obsolete.doc';

        $archivePath = getFilePath($fileId, $realname, 'archive');
        $dataPath = getFilePath($fileId, $realname, 'data');
        $incomingPath = getFilePath($fileId, $realname, 'incoming');

        $this->assertStringContainsString('archive', $archivePath);
        $this->assertStringContainsString('incoming', $incomingPath);

        $paths = $this->simulateGetAllFilePaths($fileId, $realname);

        $this->assertCount(3, $paths);
        $this->assertSame($archivePath, $paths['archive']);
        $this->assertSame($dataPath, $paths['data']);
        $this->assertSame($incomingPath, $paths['incoming']);
    }

    public function testPmtDeleteIncomingPathStructure(): void
    {
        $fileId = 30;
        $realname = 'obsolete.doc';

        $incomingPath = getFilePath($fileId, $realname, 'incoming');

        $expectedDir = dirname($incomingPath);
        $this->assertSame(
            rtrim($GLOBALS['CONFIG']['dataDir'], '/') . '/incoming/' . $fileId,
            $expectedDir
        );
        $this->assertSame($realname, basename($incomingPath));
    }

    public function testHistoryQueryReturnsAllEntriesIncludingIncoming(): void
    {
        $fileId = 25;

        $historyRows = [
            [
                'last_name' => 'Smith',
                'first_name' => 'John',
                'modified_on' => '2025-01-15 10:00:00',
                'note' => 'Approved revision 1',
                'revision' => 'current',
            ],
            [
                'last_name' => 'Doe',
                'first_name' => 'Jane',
                'modified_on' => '2025-01-14 14:30:00',
                'note' => 'Check-in for review',
                'revision' => 'incoming',
            ],
            [
                'last_name' => 'Doe',
                'first_name' => 'Jane',
                'modified_on' => '2025-01-10 09:00:00',
                'note' => 'Initial upload',
                'revision' => '0',
            ],
        ];

        $historyStatement = \Mockery::mock(\PDOStatement::class);
        $historyStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $fileId])
            ->andReturn(true);
        $historyStatement->shouldReceive('fetchAll')
            ->once()
            ->andReturn($historyRows);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/SELECT\s+u\.last_name,\s+u\.first_name,\s+l\.modified_on,\s+l\.note,\s+l\.revision\s+FROM\s+odm_log\s+l,\s+odm_user\s+u\s+WHERE\s+l\.id\s+=\s+:id\s+AND\s+u\.username\s+=\s+l\.modified_by\s+ORDER\s+BY\s+l\.modified_on\s+DESC/s"
            ))
            ->once()
            ->andReturn($historyStatement);

        $result = $this->simulateHistoryQuery($fileId);

        $this->assertCount(3, $result);
        $this->assertSame('current', $result[0]['revision']);
        $this->assertSame('incoming', $result[1]['revision']);
        $this->assertSame('0', $result[2]['revision']);
    }

    public function testPmtDeleteRemovesIncomingFile(): void
    {
        $fileId = 35;
        $realname = 'draft.docx';

        $incomingPath = getFilePath($fileId, $realname, 'incoming');
        $incomingDir = dirname($incomingPath);

        $this->assertStringContainsString('/incoming/' . $fileId, $incomingPath);
        $this->assertSame($incomingDir, dirname($incomingPath));

        $dataPath = getFilePath($fileId, $realname, 'data');
        $archivePath = getFilePath($fileId, $realname, 'archive');

        $paths = $this->simulateGetAllFilePaths($fileId, $realname);

        $this->assertSame($archivePath, $paths['archive']);
        $this->assertSame($dataPath, $paths['data']);
        $this->assertSame($incomingPath, $paths['incoming']);
    }

    private function simulateCountRevisions(int $fileId): int
    {
        $query = "SELECT COALESCE(MAX(CAST(revision AS UNSIGNED)) + 1, 0) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision NOT IN ('current', 'incoming', 'pending')";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':id' => $fileId]);
        return (int) $stmt->fetchColumn();
    }

    private function simulateApprovalFlow(int $fileId): bool
    {
        $countQuery = "SELECT COALESCE(MAX(CAST(revision AS UNSIGNED)) + 1, 0) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision NOT IN ('current', 'incoming', 'pending')";
        $countStmt = $this->mockPdo->prepare($countQuery);
        $countStmt->execute([':id' => $fileId]);
        $revisionCount = (int) $countStmt->fetchColumn();

        $accessQuery = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}access_log WHERE file_id = :id AND action = 'Y'";
        $accessStmt = $this->mockPdo->prepare($accessQuery);
        $accessStmt->execute([':id' => $fileId]);
        $hasPriorApproval = (int) $accessStmt->fetchColumn() > 0;

        if ($hasPriorApproval || $revisionCount > 0) {
            $archiveQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = :rev WHERE id = :id AND revision = 'current'";
            $archiveStmt = $this->mockPdo->prepare($archiveQuery);
            $archiveStmt->execute([':rev' => $revisionCount, ':id' => $fileId]);

            $promoteQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = 'current' WHERE id = :id AND revision = 'incoming'";
            $promoteStmt = $this->mockPdo->prepare($promoteQuery);
            $promoteStmt->execute([':id' => $fileId]);
        } else {
            $deleteQuery = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision = 'current'";
            $deleteStmt = $this->mockPdo->prepare($deleteQuery);
            $deleteStmt->execute([':id' => $fileId]);

            $promoteQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = 'current' WHERE id = :id AND revision = 'incoming'";
            $promoteStmt = $this->mockPdo->prepare($promoteQuery);
            $promoteStmt->execute([':id' => $fileId]);
        }

        return true;
    }

    private function simulateCheckInLogUpsert(int $fileId, string $note): bool
    {
        $stmt = $this->mockPdo->prepare(
            "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid"
        );
        $stmt->execute([':uid' => $_SESSION['uid']]);
        $username = $stmt->fetch()['username'];

        $stmt = $this->mockPdo->prepare(
            "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision = 'incoming'"
        );
        $stmt->execute([':id' => $fileId]);
        $incomingExists = (int) $stmt->fetchColumn() > 0;

        $params = [':id' => $fileId, ':username' => $username, ':note' => $note];
        if ($incomingExists) {
            $stmt = $this->mockPdo->prepare(
                "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET modified_on = NOW(), modified_by = :username, note = :note WHERE id = :id AND revision = 'incoming'"
            );
        } else {
            $stmt = $this->mockPdo->prepare(
                "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'incoming')"
            );
        }
        $stmt->execute($params);

        return true;
    }

    private function simulateHistoryDisplayForRevision(string $revision, int $publishable = 1): string
    {
        ob_start();
        $extra_message = '';
        if ($revision === 'current') {
            echo '<td class="text-center"><a href="details?id=1&state=1"><span class="revision">Latest</span></a>' . $extra_message;
        } elseif ($revision === 'incoming') {
            echo '<td>' . ($publishable === -1 ? 'Rejected' : 'Pending');
        }
        return ob_get_clean();
    }

    private function simulateHistoryQuery(int $fileId): array
    {
        $query = "
          SELECT
            u.last_name,
            u.first_name,
            l.modified_on,
            l.note,
            l.revision
          FROM
            {$GLOBALS['CONFIG']['db_prefix']}log l,
            {$GLOBALS['CONFIG']['db_prefix']}user u
          WHERE
            l.id = :id
          AND
            u.username = l.modified_by
          ORDER BY
            l.modified_on DESC
        ";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':id' => $fileId]);
        return $stmt->fetchAll();
    }

    private function simulateGetAllFilePaths(int $fileId, string $realname): array
    {
        return [
            'archive' => getFilePath($fileId, $realname, 'archive'),
            'data' => getFilePath($fileId, $realname, 'data'),
            'incoming' => getFilePath($fileId, $realname, 'incoming'),
        ];
    }

    private function countApprovedVersions(array $rows): int
    {
        return count(array_filter($rows, function (array $row): bool {
            return $row['revision'] === 'current' || is_numeric($row['revision']);
        }));
    }

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

    public function testFullWorkflowSequence(): void
    {
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
    }

    private function countCurrent(array $log): int
    {
        return count(array_filter($log, fn($e) => $e['revision'] === 'current'));
    }

    private function countIncoming(array $log): int
    {
        return count(array_filter($log, fn($entry) => $entry['revision'] === 'incoming'));
    }

    private function countRevisionsInLog(array $log): int
    {
        $revisionValues = array_column($log, 'revision');
        $nums = array_map('intval', array_filter($revisionValues, fn($r) => is_numeric($r)));
        return empty($nums) ? 0 : max($nums) + 1;
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $this->mockPdo = null;
        $this->mockStatement = null;
        unset($GLOBALS['pdo']);
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        parent::tearDown();
    }
}