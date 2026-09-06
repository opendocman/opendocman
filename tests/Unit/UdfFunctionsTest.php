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

        $this->assertStringContainsString('href="udf_help.html#Add_File_My Field"', $output);
        $this->assertStringContainsString("onClick=\"return popup(this,'Help')\"", $output);
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
}