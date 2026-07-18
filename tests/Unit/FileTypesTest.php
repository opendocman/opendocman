<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/FileTypes.class.php';

if (!function_exists('display_smarty_template')) {
    function display_smarty_template($tpl)
    {
        $GLOBALS['__last_rendered_template'] = $tpl;
    }
}

if (!class_exists('DummySmarty')) {
    class DummySmarty
    {
        public function assign($key, $value)
        {
            if (!isset($GLOBALS['__smarty_assignments'])) {
                $GLOBALS['__smarty_assignments'] = [];
            }
            $GLOBALS['__smarty_assignments'][$key] = $value;
        }

        public function display($path)
        {
            $GLOBALS['__last_rendered_template'] = basename($path);
        }
    }
}

class FileTypesTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';
        if (!defined('ABSPATH')) {
            define('ABSPATH', sys_get_temp_dir() . '/odm_filetypes_test/');
        }
        if (empty($GLOBALS['CONFIG']['theme'])) {
            $GLOBALS['CONFIG']['theme'] = 'default';
        }

        // Ensure theme and common template directories and expected templates exist
        $base = rtrim(ABSPATH, '/');
        if (!is_dir($base . '/views/' . $GLOBALS['CONFIG']['theme'])) {
            @mkdir($base . '/views/' . $GLOBALS['CONFIG']['theme'], 0777, true);
        }
        if (!is_dir($base . '/views/common')) {
            @mkdir($base . '/views/common', 0777, true);
        }
        if (!file_exists($base . '/views/' . $GLOBALS['CONFIG']['theme'] . '/filetypes.tpl')) {
            @file_put_contents($base . '/views/' . $GLOBALS['CONFIG']['theme'] . '/filetypes.tpl', '');
        }
        if (!file_exists($base . '/views/' . $GLOBALS['CONFIG']['theme'] . '/filetypes_deleteshow.tpl')) {
            @file_put_contents($base . '/views/' . $GLOBALS['CONFIG']['theme'] . '/filetypes_deleteshow.tpl', '');
        }
        if (!file_exists($base . '/views/common/_content.tpl')) {
            @file_put_contents($base . '/views/common/_content.tpl', '');
        }

        // Prepare a dummy Smarty-like object and reset tracking globals
        $GLOBALS['smarty'] = new DummySmarty();
        $GLOBALS['__smarty_assignments'] = [];
        $GLOBALS['__last_rendered_template'] = null;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['__smarty_assignments'], $GLOBALS['__last_rendered_template']);
        parent::tearDown();
    }

    public function testAddInsertsFileType(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $input = ['filetype' => 'pdf'];

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'INSERT INTO') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')
            ->once()
            ->with(\Mockery::on(function ($params) use ($input) {
                return is_array($params)
                    && array_key_exists(':data', $params)
                    && $params[':data'] === $input['filetype'];
            }))
            ->andReturn(true);

        $model = new FileTypes($pdo);
        $this->assertTrue($model->add($input));
    }

    public function testSaveUpdatesActiveFlagsAndSelectedIds(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtUncheck = \Mockery::mock(PDOStatement::class);
        $stmtUpdate = \Mockery::mock(PDOStatement::class);

        $data = [
            'types' => [3, 8, 12],
        ];

        // First UPDATE sets all to inactive
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'UPDATE') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false
                    && stripos($sql, "SET\n                active='0'") !== false;
            }))
            ->andReturn($stmtUncheck);
        $stmtUncheck->shouldReceive('execute')->once()->andReturn(true);

        // Subsequent UPDATEs set each provided ID to active
        $pdo->shouldReceive('prepare')
            ->times(count($data['types']))
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'UPDATE') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false
                    && stripos($sql, 'WHERE') !== false
                    && stripos($sql, 'id = :value') !== false;
            }))
            ->andReturn($stmtUpdate);

        $stmtUpdate->shouldReceive('execute')
            ->times(count($data['types']))
            ->with(\Mockery::on(function ($params) use ($data) {
                return is_array($params)
                    && array_key_exists(':value', $params)
                    && in_array($params[':value'], $data['types'], true);
            }))
            ->andReturn(true);

        $model = new FileTypes($pdo);
        $this->assertTrue($model->save($data));
    }

    public function testSaveReturnsFalseWhenNoTypesProvided(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtUncheck = \Mockery::mock(PDOStatement::class);

        // The uncheck update still occurs
        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'UPDATE') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false
                    && stripos($sql, "SET\n                active='0'") !== false;
            }))
            ->andReturn($stmtUncheck);
        $stmtUncheck->shouldReceive('execute')->once()->andReturn(true);

        $model = new FileTypes($pdo);
        $this->assertFalse($model->save([]));
    }

    public function testLoadPopulatesAllowedFileTypes(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $rows = [
            ['type' => 'pdf'],
            ['type' => 'doc'],
            ['type' => 'xlsx'],
        ];

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'SELECT') !== false
                    && stripos($sql, 'type') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false
                    && stripos($sql, "active='1'") !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn($rows);

        $model = new FileTypes($pdo);
        $model->load();

        $this->assertSame(['pdf', 'doc', 'xlsx'], $GLOBALS['CONFIG']['allowedFileTypes']);
    }

    public function testEditAssignsSmartyAndRendersTemplate(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $rows = [
            ['id' => 1, 'type' => 'pdf', 'active' => 1],
            ['id' => 2, 'type' => 'doc', 'active' => 0],
        ];

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'SELECT') !== false
                    && stripos($sql, '*') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn($rows);

        $model = new FileTypes($pdo);
        $model->edit();

        $this->assertArrayHasKey('filetypes_array', $GLOBALS['__smarty_assignments']);
        $this->assertSame($rows, $GLOBALS['__smarty_assignments']['filetypes_array']);
        $this->assertSame('_content.tpl', $GLOBALS['__last_rendered_template']);
    }

    public function testDeleteSelectAssignsAndRendersDeleteTemplate(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $rows = [
            ['id' => 3, 'type' => 'jpg', 'active' => 1],
            ['id' => 4, 'type' => 'png', 'active' => 1],
        ];

        $pdo->shouldReceive('prepare')
            ->once()
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'SELECT') !== false
                    && stripos($sql, '*') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn($rows);

        $model = new FileTypes($pdo);
        $model->deleteSelect();

        $this->assertArrayHasKey('filetypes_array', $GLOBALS['__smarty_assignments']);
        $this->assertSame($rows, $GLOBALS['__smarty_assignments']['filetypes_array']);
        $this->assertSame('_content.tpl', $GLOBALS['__last_rendered_template']);
    }

    public function testDeleteRemovesEachProvidedId(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $ids = [5, 6];

        $pdo->shouldReceive('prepare')
            ->times(count($ids))
            ->with(\Mockery::on(function ($sql) {
                return is_string($sql)
                    && stripos($sql, 'DELETE FROM') !== false
                    && stripos($sql, "{$GLOBALS['CONFIG']['db_prefix']}filetypes") !== false
                    && stripos($sql, 'WHERE') !== false
                    && stripos($sql, 'id = :id') !== false;
            }))
            ->andReturn($stmt);

        $stmt->shouldReceive('execute')
            ->times(count($ids))
            ->with(\Mockery::on(function ($params) use ($ids) {
                return is_array($params)
                    && isset($params[':id'])
                    && in_array($params[':id'], $ids, true);
            }))
            ->andReturn(true);

        $model = new FileTypes($pdo);
        $this->assertTrue($model->delete(['types' => $ids]));
    }
}
