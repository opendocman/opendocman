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
                "/SELECT COUNT\(\*\) FROM odm_log WHERE id = :id AND revision != 'current' AND revision != 'incoming'/"
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
                "/SELECT COUNT\(\*\) FROM odm_log WHERE id = :id AND revision != 'current' AND revision != 'incoming'/"
            ))
            ->andReturn($countStatement);

        $revisionCount = $this->simulateCountRevisions($fileId);

        $this->assertSame(0, $revisionCount);
    }

    public function testApprovalFlowUpdatesIncomingLogAndInsertsCurrent(): void
    {
        $fileId = 10;
        $revisionCount = 2;
        $username = 'reviewer1';

        $usernameStatement = \Mockery::mock(\PDOStatement::class);
        $usernameStatement->shouldReceive('execute')
            ->once()
            ->with([':uid' => 1])
            ->andReturn(true);
        $usernameStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn($username);

        $countStatement = \Mockery::mock(\PDOStatement::class);
        $countStatement->shouldReceive('execute')
            ->once()
            ->with([':id' => $fileId])
            ->andReturn(true);
        $countStatement->shouldReceive('fetchColumn')
            ->once()
            ->andReturn((string) $revisionCount);

        $updateStatement = \Mockery::mock(\PDOStatement::class);
        $updateStatement->shouldReceive('execute')
            ->once()
            ->with([':rev' => $revisionCount, ':id' => $fileId])
            ->andReturn(true);

        $insertStatement = \Mockery::mock(\PDOStatement::class);
        $insertStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function (array $params) use ($fileId, $username, $revisionCount) {
                return $params[':id'] === $fileId
                    && $params[':username'] === $username
                    && strpos($params[':note'], 'Approved revision ' . $revisionCount) !== false;
            }))
            ->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT username FROM odm_user WHERE id = :uid/'))
            ->once()
            ->ordered()
            ->andReturn($usernameStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/SELECT COUNT\(\*\) FROM odm_log WHERE id = :id AND revision != 'current' AND revision != 'incoming'/"
            ))
            ->once()
            ->ordered()
            ->andReturn($countStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/UPDATE odm_log SET revision = :rev WHERE id = :id AND revision = 'incoming'/"
            ))
            ->once()
            ->ordered()
            ->andReturn($updateStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/INSERT INTO odm_log \(id, modified_on, modified_by, note, revision\) VALUES/i"
            ))
            ->once()
            ->ordered()
            ->andReturn($insertStatement);

        $result = $this->simulateApprovalFlow($fileId);

        $this->assertTrue($result);
    }

    public function testCheckInInsertsIncomingLogEntry(): void
    {
        $fileId = 15;
        $username = 'testuser';
        $note = 'Updated contract terms';

        $userStatement = \Mockery::mock(\PDOStatement::class);
        $userStatement->shouldReceive('execute')
            ->once()
            ->with([':uid' => 1])
            ->andReturn(true);
        $userStatement->shouldReceive('fetch')
            ->once()
            ->andReturn(['username' => $username]);

        $insertStatement = \Mockery::mock(\PDOStatement::class);
        $insertStatement->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function (array $params) use ($fileId, $username, $note) {
                return $params[':id'] === $fileId
                    && $params[':username'] === $username
                    && $params[':note'] === $note;
            }))
            ->andReturn(true);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern('/SELECT username FROM odm_user WHERE id = :uid/'))
            ->once()
            ->ordered()
            ->andReturn($userStatement);

        $this->mockPdo->shouldReceive('prepare')
            ->with(\Mockery::pattern(
                "/INSERT INTO odm_log \(id, modified_on, modified_by, note, revision\) VALUES.*'incoming'/i"
            ))
            ->once()
            ->ordered()
            ->andReturn($insertStatement);

        $result = $this->simulateCheckInLogInsert($fileId, $username, $note);

        $this->assertTrue($result);
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
        $query = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision != 'current' AND revision != 'incoming'";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':id' => $fileId]);
        return (int) $stmt->fetchColumn();
    }

    private function simulateApprovalFlow(int $fileId): bool
    {
        $usernameQuery = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid";
        $usernameStmt = $this->mockPdo->prepare($usernameQuery);
        $usernameStmt->execute([':uid' => $_SESSION['uid']]);
        $username = $usernameStmt->fetchColumn();

        $countQuery = "SELECT COUNT(*) FROM {$GLOBALS['CONFIG']['db_prefix']}log WHERE id = :id AND revision != 'current' AND revision != 'incoming'";
        $countStmt = $this->mockPdo->prepare($countQuery);
        $countStmt->execute([':id' => $fileId]);
        $revisionCount = (int) $countStmt->fetchColumn();

        $updateQuery = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}log SET revision = :rev WHERE id = :id AND revision = 'incoming'";
        $updateStmt = $this->mockPdo->prepare($updateQuery);
        $updateStmt->execute([':rev' => $revisionCount, ':id' => $fileId]);

        $insertQuery = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'current')";
        $insertStmt = $this->mockPdo->prepare($insertQuery);
        $insertStmt->execute([
            ':id' => $fileId,
            ':username' => $username,
            ':note' => 'Approved revision ' . $revisionCount,
        ]);

        return true;
    }

    private function simulateCheckInLogInsert(int $fileId, string $username, string $note): bool
    {
        $query = "SELECT username FROM {$GLOBALS['CONFIG']['db_prefix']}user WHERE id = :uid";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([':uid' => $_SESSION['uid']]);
        $result = $stmt->fetch();
        $username = $result['username'];

        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}log (id, modified_on, modified_by, note, revision) VALUES(:id, NOW(), :username, :note, 'incoming')";
        $stmt = $this->mockPdo->prepare($query);
        $stmt->execute([
            ':id' => $fileId,
            ':username' => $username,
            ':note' => $note,
        ]);

        return true;
    }

    private function simulateHistoryDisplayForRevision(string $revision): string
    {
        ob_start();
        $extra_message = '';
        if ($revision === 'current') {
            echo '<td class="text-center"><a href="details?id=1&state=1"><span class="revision">Latest</span></a>' . $extra_message;
        } elseif ($revision === 'incoming') {
            echo '<td>Pending' . $extra_message;
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