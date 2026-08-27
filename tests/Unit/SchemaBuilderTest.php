<?php

use PHPUnit\Framework\TestCase;

require_once APPLICATION_PATH . '/installer/SchemaBuilder.php';

class SchemaBuilderTest extends TestCase
{
    public function testDumpIsDeterministicForDefaultAdminPassword(): void
    {
        $builder = new SchemaBuilder();
        $dump1 = $builder->buildFullDump('odm_', ['admin_password' => 'admin']);
        $dump2 = $builder->buildFullDump('odm_', ['admin_password' => 'admin']);

        $this->assertSame($dump1, $dump2, 'repeated dumps with the default admin password must be byte-identical');
    }

    public function testDefaultAdminSeedHashVerifiesAgainstBlankAdminPassword(): void
    {
        $builder = new SchemaBuilder();
        $statements = $builder->getDefaultDataStatements('odm_', ['admin_password' => 'admin']);

        $adminInsert = null;
        foreach ($statements as $stmt) {
            if (strpos($stmt, "INSERT INTO `odm_user`") === 0) {
                $adminInsert = $stmt;
                break;
            }
        }

        $this->assertNotNull($adminInsert, 'admin user seed statement should exist');

        // Extract the bcrypt hash: the 3rd quoted value in the INSERT.
        preg_match("/VALUES \(NULL,'admin','([^']+)'/", $adminInsert, $m);
        $this->assertNotEmpty($m[1] ?? '', 'admin seed should carry a bcrypt hash');
        $this->assertTrue(
            password_verify('admin', $m[1]),
            'pinned admin seed hash must verify against password "admin"'
        );
    }

    public function testCustomAdminPasswordIsStillHashedFresh(): void
    {
        $builder = new SchemaBuilder();
        $statements = $builder->getDefaultDataStatements('odm_', ['admin_password' => 'correct horse battery staple']);

        $adminInsert = null;
        foreach ($statements as $stmt) {
            if (strpos($stmt, "INSERT INTO `odm_user`") === 0) {
                $adminInsert = $stmt;
                break;
            }
        }
        $this->assertNotNull($adminInsert);
        preg_match("/VALUES \(NULL,'admin','([^']+)'/", $adminInsert, $m);
        $this->assertNotEmpty($m[1] ?? '');
        $this->assertTrue(password_verify('correct horse battery staple', $m[1]));
    }
}