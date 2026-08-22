<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/models/Settings.class.php';

class SettingsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tmpBase;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal CONFIG required by queries in Settings
        if (!isset($GLOBALS['CONFIG']) || !is_array($GLOBALS['CONFIG'])) {
            $GLOBALS['CONFIG'] = [];
        }
        $GLOBALS['CONFIG']['db_prefix'] = isset($GLOBALS['CONFIG']['db_prefix']) ? $GLOBALS['CONFIG']['db_prefix'] : 'odm_';
        $GLOBALS['CONFIG']['max_query'] = isset($GLOBALS['CONFIG']['max_query']) ? $GLOBALS['CONFIG']['max_query'] : 100;

        // Prepare a temporary filesystem for getFolders/getThemes/getLanguages
        $this->tmpBase = sys_get_temp_dir() . '/odm_settings_test_' . uniqid();
        $viewsPath = $this->tmpBase . '/views';
        $langPath = $this->tmpBase . '/includes/language';

        $this->mkdirp($viewsPath);
        $this->mkdirp($langPath);

        // Create some directories and files for testing
        // views: create themes and some excluded names
        $this->mkdirp($viewsPath . '/theme1');
        $this->mkdirp($viewsPath . '/theme2');
        $this->mkdirp($viewsPath . '/layouts');      // should be excluded by getFolders
        $this->mkdirp($viewsPath . '/views');        // should be excluded by getFolders
        $this->touch($viewsPath . '/README');        // should be excluded by getFolders
        $this->touch($viewsPath . '/sync.sh');       // should be excluded by getFolders

        // includes/language: languages as files and some excluded names
        $this->touch($langPath . '/en.php');
        $this->touch($langPath . '/fr.php');
        $this->mkdirp($langPath . '/common');        // should be excluded by getFolders
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpBase);
        parent::tearDown();
    }

    public function testSaveUpdatesSettings(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $data = [
            'site_url' => 'http://example.test',
            'theme' => 'bootstrap5'
        ];

        // Expect a prepare+execute per key/value
        $pdo->shouldReceive('prepare')->times(2)->andReturn($stmt);
        $stmt->shouldReceive('execute')
            ->with(\Mockery::on(function ($args) use ($data) {
                return isset($args[':key'], $args[':value']) && array_key_exists($args[':key'], $data) && $data[$args[':key']] === $args[':value'];
            }))
            ->twice()
            ->andReturn(true);

        $settings = new Settings($pdo);
        $this->assertTrue($settings->save($data));
    }

    public function testLoadPopulatesGlobalConfig(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $rows = [
            ['name' => 'site_name', 'value' => 'OpenDocMan'],
            ['name' => 'theme', 'value' => 'clean']
        ];

        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn($rows);

        $settings = new Settings($pdo);
        $settings->load();

        $this->assertSame('OpenDocMan', $GLOBALS['CONFIG']['site_name'] ?? null);
        $this->assertSame('clean', $GLOBALS['CONFIG']['theme'] ?? null);
    }

    public function testGetUserIdNumsReturnsList(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        $userRows = [
            ['id' => 1, 'username' => 'alice'],
            ['id' => 2, 'username' => 'bob']
        ];

        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $stmt->shouldReceive('execute')->once()->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn($userRows);

        $settings = new Settings($pdo);
        $result = $settings->getUserIdNums();

        $this->assertSame($userRows, $result);
    }

    public function testGetDbVersionReturnsVersionWhenTableExists(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtShow = \Mockery::mock(PDOStatement::class);
        $stmtSelect = \Mockery::mock(PDOStatement::class);

        // SHOW TABLES LIKE :table
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtShow);
        $stmtShow->shouldReceive('execute')->once()->with([':table' => 'odm_odmsys'])->andReturn(true);
        $stmtShow->shouldReceive('rowCount')->once()->andReturn(1);

        // SELECT sys_value FROM odm_odmsys WHERE sys_name='version'
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtSelect);
        $stmtSelect->shouldReceive('execute')->once()->andReturn(true);
        $stmtSelect->shouldReceive('fetch')->once()->andReturn(['sys_value' => '2.3.4']);

        $this->assertSame('2.3.4', Settings::get_db_version($pdo, 'odm_'));
    }

    public function testGetDbVersionReturnsUnknownWhenTableMissing(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $stmtShow = \Mockery::mock(PDOStatement::class);

        // SHOW TABLES LIKE :table -> rowCount 0
        $pdo->shouldReceive('prepare')->once()->andReturn($stmtShow);
        $stmtShow->shouldReceive('execute')->once()->with([':table' => 'odm_odmsys'])->andReturn(true);
        $stmtShow->shouldReceive('rowCount')->once()->andReturn(0);

        $this->assertSame('Unknown', Settings::get_db_version($pdo, 'odm_'));
    }

    public function testGetFoldersFiltersEntries(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        // Build a custom path with a mix of items
        $customPath = $this->tmpBase . '/custom';
        $this->mkdirp($customPath);
        $this->mkdirp($customPath . '/keep1');
        $this->mkdirp($customPath . '/layouts');   // should be excluded
        $this->touch($customPath . '/README');     // should be excluded
        $this->mkdirp($customPath . '/keep2');

        $folders = $settings->getFolders($customPath);

        // Should contain keep1 and keep2, not contain excluded names
        $this->assertContains('keep1', $folders);
        $this->assertContains('keep2', $folders);
        $this->assertNotContains('layouts', $folders);
        $this->assertNotContains('README', $folders);
    }

    public function testGetThemesUsesViewsDirectoryWhenAvailable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $themes = $settings->getFolders($this->tmpBase . '/views');

        $this->assertContains('theme1', $themes);
        $this->assertContains('theme2', $themes);
        $this->assertNotContains('layouts', $themes);
        $this->assertNotContains('views', $themes);
        $this->assertNotContains('README', $themes);
        $this->assertNotContains('sync.sh', $themes);
    }

    public function testGetLanguagesUsesLanguageDirectoryWhenAvailable(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $languages = str_replace('.php', '', $settings->getFolders($this->tmpBase . '/includes/language'));

        // Files en.php and fr.php should be returned without the .php extension
        $this->assertContains('en', $languages);
        $this->assertContains('fr', $languages);

        // Excluded directories should not appear
        $this->assertNotContains('common', $languages);
    }

    public function testGetReturnsNullByDefault(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $this->assertNull($settings->get('nonexistent'));
    }

    public function testEditAssignsDepartments(): void
    {
        // edit() calls getThemes()/getLanguages() which scan ABSPATH directories,
        // and display_smarty_template() which reads $GLOBALS['CONFIG']['theme'].
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->tmpBase . '/');
        }
        $this->mkdirp(ABSPATH . 'views');
        $this->mkdirp(ABSPATH . 'includes/language');
        if (empty($GLOBALS['CONFIG']['theme'])) {
            $GLOBALS['CONFIG']['theme'] = 'bootstrap5';
        }

        $pdo = \Mockery::mock(PDO::class);
        $stmt = \Mockery::mock(PDOStatement::class);

        // Query for settings rows (SELECT * FROM settings)
        $stmt->shouldReceive('execute')->andReturn(true);
        $stmt->shouldReceive('fetchAll')->andReturn([]);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT \* FROM.*settings/'))->andReturn($stmt);

        // Query for departments
        $deptStmt = \Mockery::mock(PDOStatement::class);
        $deptStmt->shouldReceive('execute')->andReturn(true);
        $deptStmt->shouldReceive('fetchAll')->andReturn([['1', 'Public'], ['2', 'HR']]);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT.*FROM.*department/'))->andReturn($deptStmt);

        // Query for user id numbers (getUserIdNums)
        $userStmt = \Mockery::mock(PDOStatement::class);
        $userStmt->shouldReceive('execute')->andReturn(true);
        $userStmt->shouldReceive('fetchAll')->andReturn([]);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT.*FROM.*user/s'))->andReturn($userStmt);

        $smarty = \Mockery::mock('Smarty');
        $smarty->shouldReceive('assign')->with('departments', \Mockery::on(function ($d) {
            return count($d) === 2 && $d[0][0] === '1' && $d[0][1] === 'Public';
        }))->once();
        $smarty->shouldReceive('assign')->withAnyArgs()->andReturnNull();
        $smarty->shouldReceive('display')->andReturnNull();
        $GLOBALS['smarty'] = $smarty;

        $settings = new Settings($pdo);
        $settings->edit();
    }

    public function testGroupSettingsOrdersAndLabelsKnownGroups(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $originalLang = $GLOBALS['lang'] ?? null;
        $GLOBALS['lang'] = ['settings_group_general' => 'General'];

        $rows = [
            ['id' => 1, 'name' => 'title', 'value' => 'My Repo', 'description' => 'title desc', 'validation' => 'maxsize=255'],
            ['id' => 2, 'name' => 'authen', 'value' => 'mysql', 'description' => 'auth', 'validation' => ''],
        ];

        $groups = $settings->groupSettings($rows);

        $this->assertArrayHasKey('general', $groups);
        $this->assertArrayHasKey('security', $groups);
        $this->assertCount(1, $groups['general']['settings']);
        $this->assertSame('title', $groups['general']['settings'][0]['name']);
        $this->assertSame('General', $groups['general']['label']);
        $this->assertArrayNotHasKey('other', $groups);
        $this->assertTrue(array_key_exists('general', $groups) && reset($groups)['name'] === 'general');

        if ($originalLang === null) {
            unset($GLOBALS['lang']);
        } else {
            $GLOBALS['lang'] = $originalLang;
        }
    }

    public function testGroupSettingsUnknownFallsBackToOther(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $rows = [
            ['id' => 1, 'name' => 'brand_new_setting', 'value' => 'x', 'description' => 'd', 'validation' => ''],
            ['id' => 2, 'name' => 'another_unknown', 'value' => 'y', 'description' => 'e', 'validation' => 'bool'],
        ];

        $groups = $settings->groupSettings($rows);

        $this->assertArrayHasKey('other', $groups);
        $this->assertCount(2, $groups['other']['settings']);
        $this->assertSame('other', $groups['other']['name']);
    }

    public function testGroupSettingsMailPrefixGoesToEmail(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $rows = [
            ['id' => 1, 'name' => 'mail_host', 'value' => 'imap.example.com', 'description' => 'd', 'validation' => ''],
            ['id' => 2, 'name' => 'site_mail', 'value' => 'a@b.co', 'description' => 'd', 'validation' => ''],
        ];

        $groups = $settings->groupSettings($rows);

        $this->assertArrayHasKey('email', $groups);
        $this->assertArrayNotHasKey('other', $groups);
    }

    public function testGroupSettingsOrderIsFixed(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $rows = [
            ['id' => 1, 'name' => 'title', 'value' => '', 'description' => '', 'validation' => ''],
            ['id' => 2, 'name' => 'language', 'value' => '', 'description' => '', 'validation' => ''],
            ['id' => 3, 'name' => 'dataDir', 'value' => '', 'description' => '', 'validation' => ''],
        ];

        $groups = $settings->groupSettings($rows);

        $this->assertSame(['general', 'storage', 'appearance'], array_keys($groups));
    }

    public function testGroupSettingsEmptyRowsReturnsEmptyArray(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $this->assertSame([], $settings->groupSettings([]));
    }

    public function testGroupSettingsPublicSharingGoesToGeneral(): void
    {
        $pdo = \Mockery::mock(PDO::class);
        $settings = new Settings($pdo);

        $rows = [
            ['id' => 1, 'name' => 'public_sharing', 'value' => 'true', 'description' => 'd', 'validation' => 'bool'],
        ];

        $groups = $settings->groupSettings($rows);

        $this->assertArrayHasKey('general', $groups);
        $this->assertSame('public_sharing', $groups['general']['settings'][0]['name']);
        $this->assertArrayNotHasKey('other', $groups);
    }

    private function mkdirp(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function touch(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $this->mkdirp($dir);
        }
        file_put_contents($path, '');
    }

    private function rrmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = array_diff(scandir($path), ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->rrmdir($itemPath);
            } else {
                @unlink($itemPath);
            }
        }
        @rmdir($path);
    }
}
