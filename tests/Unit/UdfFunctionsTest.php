<?php

use Aura\Html\Escaper;
use Aura\Html\Escaper\AttrEscaper;
use Aura\Html\Escaper\CssEscaper;
use Aura\Html\Escaper\HtmlEscaper;
use Aura\Html\Escaper\JsEscaper;

require_once dirname(__DIR__) . '/../application/controllers/helpers/udf_functions.php';

/**
 * Tests for the UDF "Add File" form rendering, specifically the
 * conditional help-popup link on the UDF field label.
 */
class UdfFunctionsTest extends TestCase
{
    protected string $tempDocroot;

    protected function setUp(): void
    {
        parent::setUp();

        // e::h() needs Aura's static escaper; production sets it via
        // HelperLocatorFactory, the test bootstrap does not.
        Escaper::setStatic(new Escaper(new HtmlEscaper(), new AttrEscaper(new HtmlEscaper()), new CssEscaper(), new JsEscaper()));

        // Some tests (e.g. OutControllerTest) unset $GLOBALS['CONFIG'] in
        // tearDown(), so restore the prefix rather than relying on the
        // bootstrap having set it.
        $GLOBALS['CONFIG']['db_prefix'] = 'odm_';

        $this->tempDocroot = sys_get_temp_dir() . '/odm_udf_test_' . uniqid();
        mkdir($this->tempDocroot);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        unset($_REQUEST['id']);
        if (file_exists($this->tempDocroot . '/udf_help.html')) {
            unlink($this->tempDocroot . '/udf_help.html');
        }
        rmdir($this->tempDocroot);
        parent::tearDown();
    }

    private function createMockUdfStatement(): object
    {
        $stmt = \Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmt->shouldReceive('fetchAll')->once()->andReturn([['myfield', 3, 'My Field']]);
        return $stmt;
    }

    private function bindGlobalPdo(object $stmt): void
    {
        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($stmt);
    }

    public function testUdfAddFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot(): void
    {
        file_put_contents($this->tempDocroot . '/udf_help.html', '<html></html>');
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;

        $this->bindGlobalPdo($this->createMockUdfStatement());

        ob_start();
        udf_add_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('href="udf_help.html#Add_File_Type_3"', $output);
        $this->assertStringContainsString("onClick=\"return popup(this,'Help')\"", $output);
        $this->assertStringContainsString('>My Field</a>', $output);
    }

    public function testUdfAddFileFormOutputsPlainTextWhenHelpFileMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;

        $this->bindGlobalPdo($this->createMockUdfStatement());

        ob_start();
        udf_add_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('<tr><td>My Field</td>', $output);
        $this->assertStringNotContainsString('popup(this', $output);
    }

    public function testUdfEditFileFormOutputsHelpLinkWhenHelpFileExistsInDocroot(): void
    {
        file_put_contents($this->tempDocroot . '/udf_help.html', '<html></html>');
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My Field', 3, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['current value']);

        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData);

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('href="udf_help.html#Add_File_Type_3"', $output);
        $this->assertStringContainsString('>My Field</a>', $output);
    }

    public function testUdfEditFileFormOutputsPlainTextWhenHelpFileMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My Field', 3, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['current value']);

        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData);

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('My Field', $output);
        $this->assertStringNotContainsString('popup(this', $output);
    }

    public function testUdfEditFileFormEscapesType1DisplayNameWhenHelpFileExists(): void
    {
        file_put_contents($this->tempDocroot . '/udf_help.html', '<html></html>');
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My <script>Field</script>', 1, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['1']);

        $stmtValues = \Mockery::mock(\PDOStatement::class);
        $stmtValues->shouldReceive('execute')->once()->andReturn(true);
        $stmtValues->shouldReceive('fetchAll')->once()->andReturn([['1', 'Option A']]);

        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData, $stmtValues);

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('My &lt;script&gt;Field&lt;/script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testUdfEditFileFormEscapesType1DisplayNameWhenHelpFileMissing(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $this->tempDocroot;
        $_REQUEST['id'] = 5;

        $stmtUdf = \Mockery::mock(\PDOStatement::class);
        $stmtUdf->shouldReceive('execute')->once()->with([])->andReturn(true);
        $stmtUdf->shouldReceive('fetchAll')->once()->andReturn([['My <script>Field</script>', 1, 'myfield']]);

        $stmtData = \Mockery::mock(\PDOStatement::class);
        $stmtData->shouldReceive('execute')->once()->with([':id' => 5])->andReturn(true);
        $stmtData->shouldReceive('fetch')->once()->andReturn(['1']);

        $stmtValues = \Mockery::mock(\PDOStatement::class);
        $stmtValues->shouldReceive('execute')->once()->andReturn(true);
        $stmtValues->shouldReceive('fetchAll')->once()->andReturn([['1', 'Option A']]);

        global $pdo;
        $pdo = \Mockery::mock(PDO::class);
        $pdo->shouldReceive('prepare')->andReturn($stmtUdf, $stmtData, $stmtValues);

        ob_start();
        udf_edit_file_form();
        $output = ob_get_clean();

        $this->assertStringContainsString('My &lt;script&gt;Field&lt;/script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('popup(this', $output);
    }
}