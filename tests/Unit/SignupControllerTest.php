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

        // Dependencies used by the post-submit form rendering in signup.php.
        if (!defined('ABSPATH')) {
            define('ABSPATH', APPLICATION_PATH . '/');
        }
        if (!class_exists('crumb')) {
            require_once APPLICATION_PATH . '/controllers/helpers/crumb.php';
        }
        // So msg() returns the real English copy.
        if (!isset($GLOBALS['lang'])) {
            include APPLICATION_PATH . '/includes/language/english.php';
            $GLOBALS['lang'] = $lang;
        }
        // A "signup succeeded" flow in signup.php renders the content template
        // and then calls exit(). The stub throws instead so execution stops
        // before the controller's exit kills PHPUnit — the captured 'content'
        // assignment is available for assertions.
        $GLOBALS['smarty'] = new class {
            public $content = null;

            public function assign(...$args)
            {
                if ($args[0] === 'content') {
                    $this->content = $args[1];
                }
            }

            public function display(...$args)
            {
                if (strpos((string) ($args[0] ?? ''), '_content.tpl') !== false) {
                    throw new RuntimeException('signup-content-displayed');
                }
            }
        };
        $GLOBALS['csrf'] = new class {
            public function getTokenField(): string { return ''; }
            public function validateToken(array $post): bool { return true; }
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

    /**
     * Run signup.php's submit branch. signup.php calls exit() after rendering
     * the success content template; the smarty stub raises RuntimeException
     * during that display, which breaks out before the exit.
     */
    private function runSignupSubmit(array $config, array $post): void
    {
        $GLOBALS['CONFIG'] = $config + [
            'db_prefix' => 'odm_',
            'allow_signup' => 'True',
            'base_url' => 'http://localhost',
            'title' => 'OpenDocMan',
            'theme' => 'default',
            'default_signup_department' => '1',
        ];
        $GLOBALS['pdo'] = $this->buildPdoWithInsertAssert('1');
        $_POST = $post;
        $_REQUEST = $post;
        // signup.php's success render calls draw_header(..., $last_message);
        // the controller relies on this being set in the calling scope.
        $last_message = '';
        ob_start();
        try {
            require APPLICATION_PATH . '/controllers/signup.php';
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'signup-content-displayed') {
                throw $e;
            }
        }
        ob_end_clean();
    }

    public function testSignupUsesConfiguredDefaultDepartment(): void
    {
        $this->runSignupSubmit(
            ['authen' => 'ldap'],
            [
                'adduser' => '1',
                'username' => 'newbie',
                'password' => 'secret',
                'department' => '2', // must be ignored
                'Email' => 'n@e.com',
                'last_name' => 'New',
                'first_name' => 'User',
            ]
        );
        $this->assertTrue(true);
    }

    public function testSignupSuccessRendersStyledMessageWithoutEmailNotice(): void
    {
        $this->runSignupSubmit(
            ['authen' => 'ldap'],
            [
                'adduser' => '1',
                'username' => 'newbie',
                'password' => 'secret',
                'department' => '2',
                'Email' => 'n@e.com',
                'last_name' => 'New',
                'first_name' => 'User',
            ]
        );
        $this->assertIsString($GLOBALS['smarty']->content);
        $this->assertStringContainsString('Your account has been created.', $GLOBALS['smarty']->content);
        $this->assertStringNotContainsString('check your email', $GLOBALS['smarty']->content);
        // Non-mysql auth must not leak a temp password.
        $this->assertStringNotContainsString('Your randomly generated password is', $GLOBALS['smarty']->content);
    }

    public function testSignupSuccessMysqlShowsTempPassword(): void
    {
        $this->runSignupSubmit(
            ['authen' => 'mysql'],
            [
                'adduser' => '1',
                'username' => 'newbie',
                'password' => 'TempPass123',
                'department' => '2',
                'Email' => 'n@e.com',
                'last_name' => 'New',
                'first_name' => 'User',
            ]
        );
        $this->assertIsString($GLOBALS['smarty']->content);
        $this->assertStringContainsString('Your account has been created.', $GLOBALS['smarty']->content);
        $this->assertStringContainsString('Your randomly generated password is', $GLOBALS['smarty']->content);
        $this->assertStringContainsString('TempPass123', $GLOBALS['smarty']->content);
        $this->assertStringNotContainsString('check your email', $GLOBALS['smarty']->content);
    }
}