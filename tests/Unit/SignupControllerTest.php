<?php

use Aura\Html\Escaper;
use Aura\Html\Escaper\AttrEscaper;
use Aura\Html\Escaper\CssEscaper;
use Aura\Html\Escaper\HtmlEscaper;
use Aura\Html\Escaper\JsEscaper;
use PHPUnit\Framework\TestCase;

class SignupControllerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        // e::h() needs Aura's static escaper; production sets it via
        // HelperLocatorFactory, the test bootstrap does not.
        Escaper::setStatic(new Escaper(new HtmlEscaper(), new AttrEscaper(new HtmlEscaper()), new CssEscaper(), new JsEscaper()));

        // Dependencies used by the post-submit form rendering in signup.php
        // (only reached when authen != 'mysql', i.e. no early exit()).
        if (!defined('ABSPATH')) {
            define('ABSPATH', APPLICATION_PATH . '/');
        }
        if (!class_exists('crumb')) {
            require_once APPLICATION_PATH . '/controllers/helpers/crumb.php';
        }
        $GLOBALS['smarty'] = new class {
            public function assign(...$args)
            {
            }
            public function display(...$args)
            {
            }
        };
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/signup';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['smarty']);
        unset($GLOBALS['pdo']);
        unset($GLOBALS['CONFIG']);
        unset($GLOBALS['csrf']);
        $_SESSION = [];
        $_POST = [];
        $_REQUEST = [];
        unset($_SERVER['REQUEST_URI']);
        // Aura's static escaper cannot be reset to null (setStatic is typed to
        // Escaper); setUp() always overwrites it with a fresh instance, so it is
        // idempotent between tests and needs no explicit reset here.
        parent::tearDown();
    }

    private function buildPdoWithInsertAssert($expectedDept): PDO
    {
        $pdo = \Mockery::mock(PDO::class);

        // Existence check SELECT
        $checkStmt = \Mockery::mock(\PDOStatement::class);
        $checkStmt->shouldReceive('bindParam')->andReturn(true);
        $checkStmt->shouldReceive('execute')->andReturn(true);
        $checkStmt->shouldReceive('rowCount')->andReturn(0);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/SELECT.*username.*FROM.*user/s'))->andReturn($checkStmt);

        // INSERT
        $insStmt = \Mockery::mock(\PDOStatement::class);
        $insStmt->shouldReceive('bindParam')->andReturn(true);
        $insStmt->shouldReceive('execute')->once()->with(\Mockery::on(function ($params) use ($expectedDept) {
            return $params[':department'] === $expectedDept;
        }))->andReturn(true);
        $pdo->shouldReceive('prepare')->with(\Mockery::pattern('/INSERT INTO.*user/s'))->andReturn($insStmt);
        $pdo->shouldReceive('lastInsertId')->andReturn('42');

        return $pdo;
    }

    public function testSignupUsesConfiguredDefaultDepartment(): void
    {
        $GLOBALS['CONFIG'] = [
            'db_prefix' => 'odm_',
            'allow_signup' => 'True',
            'authen' => 'ldap', // non-mysql: avoids the early exit() in the submit branch
            'base_url' => 'http://localhost',
            'title' => 'OpenDocMan',
            'theme' => 'default',
            'default_signup_department' => '1',
        ];
        $GLOBALS['pdo'] = $this->buildPdoWithInsertAssert('1');
        $GLOBALS['csrf'] = new class {
            public function getTokenField(): string { return ''; }
            public function validateToken(array $post): bool { return true; }
        };
        // Prevent draw_header / msg output side effects
        $_POST = [
            'adduser' => '1',
            'username' => 'newbie',
            'password' => 'secret',
            'department' => '2', // must be ignored
            'Email' => 'n@e.com',
            'last_name' => 'New',
            'first_name' => 'User',
        ];
        $_REQUEST = $_POST;
        $last_message = ''; // signup.php renders draw_header(msg('signup'), $last_message) after submit
        ob_start();
        require APPLICATION_PATH . '/controllers/signup.php';
        ob_end_clean();
        $this->assertTrue(true);
    }
}
