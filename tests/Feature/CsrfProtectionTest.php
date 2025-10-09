<?php
declare(strict_types=1);

namespace OpenDocMan\Tests\Feature;

use PHPUnit\Framework\TestCase;

// Load the application's CSRF wrapper (this pulls in vendor autoload internally)
require_once dirname(__DIR__, 2) . '/application/includes/csrf/CsrfProtection.php';

/**
 * Functional CSRF protection tests.
 *
 * These tests exercise the CsrfProtection wrapper around ParagonIE AntiCSRF
 * by simulating HTTP requests through superglobals and ensuring tokens validate
 * (or fail) as expected across typical flows and edge cases.
 */
final class CsrfProtectionTest extends TestCase
{
    private int $obLevel = 0;
    /**
     * Ensure each test runs with a fresh, predictable environment.
     */
    protected function setUp(): void
    {
        // Start output buffering and track initial level to balance buffers
        $this->obLevel = ob_get_level();
        if (ob_get_level() === 0) {
            ob_start();
        }
        $this->resetEnv('/toBePublished');
    }

    protected function tearDown(): void
    {
        // Clean output buffer(s) and reset session to avoid test bleed-through
        while (ob_get_level() > $this->obLevel) {
            ob_end_clean();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
        }
    }

    /**
     * Reset superglobals and session for a clean test environment.
     *
     * @param string $path The REQUEST_URI to set for the test.
     */
    private function resetEnv(string $path = '/toBePublished'): void
    {
        // Reset superglobals
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_FILES = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $path,
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        // Reset session
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            @session_unset();
            @session_destroy();
        }
        // Start a new session
        @session_start();
        $_SESSION = [];

        // Ensure consistent default state
        $this->assertSame('127.0.0.1', $_SERVER['REMOTE_ADDR']);
        $this->assertSame($path, $_SERVER['REQUEST_URI']);
    }

    /**
     * Helper: Generate a CSRF token bundle for a given path and parse hidden inputs.
     *
     * @param string $lockTo
     * @return array{field_html: string, token: string, field_name: string, index_name: string, fields: array<string,string>}
     */
    private function makeTokenFor(string $lockTo = '/toBePublished'): array
    {
        // Make sure our REQUEST_URI reflects the lock target for predictable behavior
        $_SERVER['REQUEST_URI'] = $lockTo;

        // Generate token via wrapper (this creates hidden inputs HTML)
        $csrf = \CsrfProtection::getInstance();
        $bundle = $csrf->getTokenForTemplate($lockTo);

        $this->assertIsArray($bundle);
        $this->assertArrayHasKey('field', $bundle);
        $this->assertArrayHasKey('token', $bundle);
        $this->assertArrayHasKey('field_name', $bundle);
        $this->assertArrayHasKey('index_name', $bundle);

        $hiddenHtml = (string) $bundle['field'];
        $fieldsAssoc = $this->parseHiddenInputs($hiddenHtml);

        // Basic sanity checks
        $this->assertArrayHasKey($bundle['index_name'], $fieldsAssoc, 'Index input not found in token HTML');
        $this->assertArrayHasKey($bundle['field_name'], $fieldsAssoc, 'Token input not found in token HTML');
        $this->assertNotEmpty($fieldsAssoc[$bundle['index_name']], 'Index value missing');
        $this->assertNotEmpty($fieldsAssoc[$bundle['field_name']], 'Token value missing');

        return [
            'field_html' => $hiddenHtml,
            'token' => (string) $bundle['token'],
            'field_name' => (string) $bundle['field_name'],
            'index_name' => (string) $bundle['index_name'],
            'fields' => $fieldsAssoc,
        ];
    }

    /**
     * Very small HTML parser to extract name/value from hidden inputs in the provided HTML snippet.
     *
     * @param string $html
     * @return array<string, string>
     */
    private function parseHiddenInputs(string $html): array
    {
        $result = [];
        // Match: <input type="hidden" name="..." value="..." />
        $pattern = '/<input\s+[^>]*name="([^"]+)"\s+[^>]*value="([^"]+)"[^>]*>/i';
        if (\preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = $m[1];
                $value = $m[2];
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public function testTokenFieldContainsIndexAndToken(): void
    {
        $bundle = $this->makeTokenFor('/toBePublished');

        $this->assertStringContainsString($bundle['index_name'], $bundle['field_html']);
        $this->assertStringContainsString($bundle['field_name'], $bundle['field_html']);
        $this->assertNotEmpty($bundle['token'], 'Raw token value should not be empty');
    }

    public function testValidateSucceedsWithCorrectTokenAndLock(): void
    {
        $this->resetEnv('/toBePublished');
        $bundle = $this->makeTokenFor('/toBePublished');

        // Simulate POST submission of the token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        // Validate against correct lock path
        $csrf = \CsrfProtection::getInstance();
        $this->assertTrue($csrf->validateToken($_POST, '/toBePublished'));
    }

    public function testValidateFailsWhenTokenMissing(): void
    {
        $this->resetEnv('/toBePublished');
        // Do not generate a token; submit empty POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];

        $csrf = \CsrfProtection::getInstance();
        $this->assertFalse($csrf->validateToken($_POST, '/toBePublished'));
    }

    public function testValidateFailsWithWrongLock(): void
    {
        $this->resetEnv('/toBePublished');
        $bundle = $this->makeTokenFor('/toBePublished');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        $csrf = \CsrfProtection::getInstance();
        // Validate against an incorrect lock path to trigger a failure
        $this->assertFalse($csrf->validateToken($_POST, '/some-other-path'));
    }

    public function testTokenIsConsumedAfterValidation(): void
    {
        $this->resetEnv('/toBePublished');
        $bundle = $this->makeTokenFor('/toBePublished');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        $csrf = \CsrfProtection::getInstance();

        // First validation should pass
        $this->assertTrue($csrf->validateToken($_POST, '/toBePublished'));

        // Second validation with the same token should fail (consumed)
        $this->assertFalse($csrf->validateToken($_POST, '/toBePublished'));
    }

    public function testTwoStepApproveFlow(): void
    {
        // Step 1: GET list page, generate token for list form
        $this->resetEnv('/toBePublished');
        $list = $this->makeTokenFor('/toBePublished');

        // Simulate first POST (commentAuthorize)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $_POST[$list['index_name']] = $list['fields'][$list['index_name']];
        $_POST[$list['field_name']] = $list['fields'][$list['field_name']];

        $csrf = \CsrfProtection::getInstance();
        $this->assertTrue($csrf->validateToken($_POST, '/toBePublished'), 'First step token should validate');

        // Step 2: render comment form and generate fresh token
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $comment = $this->makeTokenFor('/toBePublished');

        // Simulate second POST (Authorize)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $_POST[$comment['index_name']] = $comment['fields'][$comment['index_name']];
        $_POST[$comment['field_name']] = $comment['fields'][$comment['field_name']];

        $this->assertTrue($csrf->validateToken($_POST, '/toBePublished'), 'Second step token should validate');
    }

    /**
     * Data provider: CSRF-protected endpoints that require tokens on POST.
     *
     * @return array<int, array{0:string}>
     */
    public function providerEndpointsBasic(): array
    {
        return [
            ['/toBePublished'],
            ['/add'],
            ['/edit'],
            ['/check-in'],
            ['/filetypes'],
            ['/settings'],
            ['/rejects'],
        ];
    }

    /**
     * Data provider: endpoints that use a two-step POST flow.
     *
     * @return array<int, array{0:string}>
     */
    public function providerEndpointsTwoStep(): array
    {
        // At minimum, toBePublished has a 2-step approve/reject flow.
        return [
            ['/toBePublished'],
        ];
    }

    /**
     * @dataProvider providerEndpointsBasic
     */
    public function testValidateSucceedsWithCorrectTokenAndLockForEndpoints(string $path): void
    {
        $this->resetEnv($path);
        $bundle = $this->makeTokenFor($path);

        // Simulate POST submission of the token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        $csrf = \CsrfProtection::getInstance();
        $this->assertTrue($csrf->validateToken($_POST, $path), "CSRF validate should succeed for {$path}");
    }

    /**
     * @dataProvider providerEndpointsBasic
     */
    public function testValidateFailsWithWrongLockForEndpoints(string $path): void
    {
        $this->resetEnv($path);
        $bundle = $this->makeTokenFor($path);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        $csrf = \CsrfProtection::getInstance();
        $this->assertFalse($csrf->validateToken($_POST, '/wrong-lock'), "CSRF validate should fail with wrong lock for {$path}");
    }

    /**
     * @dataProvider providerEndpointsBasic
     */
    public function testTokenIsConsumedAfterValidationForEndpoints(string $path): void
    {
        $this->resetEnv($path);
        $bundle = $this->makeTokenFor($path);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$bundle['index_name']] = $bundle['fields'][$bundle['index_name']];
        $_POST[$bundle['field_name']] = $bundle['fields'][$bundle['field_name']];

        $csrf = \CsrfProtection::getInstance();

        $this->assertTrue($csrf->validateToken($_POST, $path), "First validation should pass for {$path}");
        $this->assertFalse($csrf->validateToken($_POST, $path), "Second validation should fail (consumed) for {$path}");
    }

    /**
     * @dataProvider providerEndpointsTwoStep
     */
    public function testTwoStepFlowForEndpoints(string $path): void
    {
        // Step 1: generate token for the first form and validate
        $this->resetEnv($path);
        $step1 = $this->makeTokenFor($path);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $_POST[$step1['index_name']] = $step1['fields'][$step1['index_name']];
        $_POST[$step1['field_name']] = $step1['fields'][$step1['field_name']];

        $csrf = \CsrfProtection::getInstance();
        $this->assertTrue($csrf->validateToken($_POST, $path), "First step token should validate for {$path}");

        // Step 2: render next form (e.g., comment form) with a fresh token and validate
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $step2 = $this->makeTokenFor($path);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $_POST[$step2['index_name']] = $step2['fields'][$step2['index_name']];
        $_POST[$step2['field_name']] = $step2['fields'][$step2['field_name']];

        $this->assertTrue($csrf->validateToken($_POST, $path), "Second step token should validate for {$path}");
    }
}
