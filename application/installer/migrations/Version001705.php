<?php

require_once __DIR__ . '/MigrationInterface.php';

class Version001705 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.7.5';
    }

    public function getDescription(): string
    {
        return 'Add email ingest: user.mail_token, email_audit table, mail settings';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec(
            "ALTER TABLE `{$prefix}user` ADD COLUMN mail_token varchar(255) NULL DEFAULT NULL"
        );

        $pdo->exec(
            "CREATE TABLE `{$prefix}email_audit` (
                id int(11) unsigned NOT NULL auto_increment,
                message_id varchar(255) default NULL,
                from_address varchar(255) default NULL,
                token_hash varchar(64) default NULL,
                outcome varchar(20) NOT NULL,
                document_id int(11) unsigned default NULL,
                reason text default NULL,
                created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE = MYISAM"
        );

        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_enabled', 'False', 'Enable the email document ingest feature', 'bool')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_host', '', 'Mail server host for the ingest mailbox', 'alpha|req')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_port', '993', 'Mail server port', 'num')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_protocol', 'imap', 'Mailbox protocol: imap or pop3', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_encryption', 'ssl', 'Mailbox encryption: none, ssl, or tls', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_user', '', 'Mailbox username', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_pass', '', 'Mailbox password', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_folder', 'INBOX', 'Mailbox folder to poll', 'alpha')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_validate_cert', 'True', 'Verify the TLS certificate when connecting to the mail server. Disable only if the mailbox hostname does not match the certificate', 'bool')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_delete', 'False', 'Delete processed messages from the mailbox after ingestion', 'bool')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_default_category', '', 'Default category id for ingested documents', '')");
        $pdo->exec("INSERT INTO `{$prefix}settings` VALUES(NULL, 'mail_default_department', '', 'Default department id for ingested documents', '')");
    }

    public function down(PDO $pdo, string $prefix): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `{$prefix}email_audit`");
        $pdo->exec("ALTER TABLE `{$prefix}user` DROP COLUMN mail_token");
        foreach (['mail_enabled','mail_host','mail_port','mail_protocol','mail_encryption','mail_user','mail_pass','mail_folder','mail_validate_cert','mail_delete','mail_default_category','mail_default_department'] as $name) {
            $pdo->exec("DELETE FROM `{$prefix}settings` WHERE name = '{$name}'");
        }
    }
}
