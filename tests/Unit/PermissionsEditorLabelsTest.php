<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/controllers/helpers/functions.php';

/**
 * Verifies the JS permissions editor has full translation coverage:
 * - perm_editor_labels() returns every key the editor needs
 * - every language file defines the label keys (new + reused addpage_*)
 */
class PermissionsEditorLabelsTest extends TestCase
{
    private array $expectedLabelKeys = [
        'forbidden', 'view', 'read', 'write', 'admin', 'unset', 'edit', 'overview',
        'add', 'name', 'type', 'inheritedFrom', 'inherited', 'deptPrefix',
        'userPrefix', 'noPermissions', 'category',
    ];

    private array $requiredLangKeys = [
        'filepermissionspage_perm_overview',
        'filepermissionspage_perm_add',
        'filepermissionspage_perm_inherited_from',
        'filepermissionspage_perm_inherited',
        'filepermissionspage_perm_no_permissions',
        'addpage_forbidden',
        'addpage_none',
        'addpage_view',
        'addpage_read',
        'addpage_write',
        'addpage_admin',
        'edit',
        'label_name',
        'type',
        'department',
        'label_user',
        'category',
    ];

    public function testPermEditorLabelsReturnsAllExpectedKeys(): void
    {
        $labels = perm_editor_labels();
        foreach ($this->expectedLabelKeys as $key) {
            $this->assertArrayHasKey($key, $labels, "Missing label key: {$key}");
        }
    }

    public function testPermEditorLabelsValuesAreNonEmpty(): void
    {
        $labels = perm_editor_labels();
        foreach ($this->expectedLabelKeys as $key) {
            $this->assertNotSame('', $labels[$key], "Empty label for: {$key}");
        }
    }

    public function testEveryLanguageFileDefinesRequiredPermissionKeys(): void
    {
        $langFiles = glob(APPLICATION_PATH . '/includes/language/*.php');
        $this->assertGreaterThan(0, count($langFiles));

        foreach ($langFiles as $file) {
            $content = file_get_contents($file);
            foreach ($this->requiredLangKeys as $key) {
                $this->assertTrue(
                    str_contains($content, "\$lang['{$key}']"),
                    basename($file) . " is missing lang key: {$key}"
                );
            }
        }
    }
}