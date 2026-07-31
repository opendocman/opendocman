<?php
namespace Test\Unit;

use PHPUnit\Framework\TestCase;
use Snapshot;

require_once APPLICATION_PATH . '/models/Snapshot.class.php';

class SnapshotTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-29 12:00:00');
        $snapshot = new Snapshot(
            name: 'demo-baseline',
            createdAt: $createdAt,
            appVersion: '2.3.0',
            description: 'Demo baseline with sample docs',
            dbSize: 1024,
            filesSize: 2048
        );

        $this->assertSame('demo-baseline', $snapshot->name);
        $this->assertSame($createdAt, $snapshot->createdAt);
        $this->assertSame('2.3.0', $snapshot->appVersion);
        $this->assertSame('Demo baseline with sample docs', $snapshot->description);
        $this->assertSame(1024, $snapshot->dbSize);
        $this->assertSame(2048, $snapshot->filesSize);
    }

    public function testConstructorAllowsNullDescription(): void
    {
        $snapshot = new Snapshot(
            name: 'auto-backup',
            createdAt: new \DateTimeImmutable(),
            appVersion: '2.3.0',
            description: null,
            dbSize: 0,
            filesSize: 0
        );

        $this->assertNull($snapshot->description);
    }

    public function testFromJsonArray(): void
    {
        $data = [
            'name' => 'test-snap',
            'created_at' => '2026-07-29T12:00:00+00:00',
            'app_version' => '2.3.0',
            'description' => 'Test snapshot',
            'db_size' => 512,
            'files_size' => 1024,
        ];

        $snapshot = Snapshot::fromJsonArray($data);

        $this->assertSame('test-snap', $snapshot->name);
        $this->assertSame('2026-07-29T12:00:00+00:00', $snapshot->createdAt->format('c'));
        $this->assertSame('Test snapshot', $snapshot->description);
        $this->assertSame(512, $snapshot->dbSize);
        $this->assertSame(1024, $snapshot->filesSize);
    }

    public function testToJsonArray(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-29T12:00:00+00:00');
        $snapshot = new Snapshot(
            name: 'test-snap',
            createdAt: $createdAt,
            appVersion: '2.3.0',
            description: 'Test snapshot',
            dbSize: 512,
            filesSize: 1024
        );

        $array = $snapshot->toJsonArray();

        $this->assertSame('test-snap', $array['name']);
        $this->assertSame('2026-07-29T12:00:00+00:00', $array['created_at']);
        $this->assertSame('2.3.0', $array['app_version']);
        $this->assertSame('Test snapshot', $array['description']);
        $this->assertSame(512, $array['db_size']);
        $this->assertSame(1024, $array['files_size']);
    }
}