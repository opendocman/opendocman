<?php

class Version001257 implements MigrationInterface
{
    public function getVersion(): string
    {
        return '1.2.5.7';
    }

    public function getDescription(): string
    {
        return 'Upgrade from 1.2.5.7/1.2.6beta - Create settings and filetypes tables';
    }

    public function up(PDO $pdo, string $prefix): void
    {
        $pdo->exec("UPDATE {$prefix}odmsys SET sys_value='1.2.6' WHERE sys_name='version'");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `name` VARCHAR(255) NOT NULL ,
            `value` VARCHAR(255) NOT NULL ,
            `description` VARCHAR(255) NOT NULL ,
            `validation` VARCHAR(255) NOT NULL ,
            PRIMARY KEY (`id`),
            UNIQUE (`name`)
        ) ENGINE = MYISAM");

        $settings = [
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'title', 'Document Repository', 'This is the browser window title', 'maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'dataDir', '/var/www/document_repository/', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'secureurl', 'True', 'Secure URL control: On or Off (case sensitive). When set to \"On\", all urls will be secured. When set to \"Off\", all urls are normal and readable', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'allow_signup', 'False', 'Should we display the sign-up link?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'theme', 'tweeter', 'Which theme to use?', '')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL,'base_url', '', 'Set this to the url of the site. No need for trailing \"/\" here', 'url')",
        ];
        foreach ($settings as $sql) {
            $pdo->exec($sql);
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `{$prefix}filetypes` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(255) NOT NULL,
            `active` TINYINT(4) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE = MYISAM");

        $filetypes = [
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/gif', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/html', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/plain', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/pdf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-pdf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/msword', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/jpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/pjpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/png', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/msexcel', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/msaccess', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/richtxt', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/mspowerpoint', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/octet-stream', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/zip', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-zip', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-zip-compressed', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/tiff', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/tif', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.ms-powerpoint', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.ms-excel', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-master', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-web', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/csv', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'audio/mpeg', 0)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/x-dwg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/x-dfx', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'drawing/x-dwf', 1)",
        ];
        foreach ($filetypes as $sql) {
            $pdo->exec($sql);
        }
    }

    public function down(PDO $pdo, string $prefix): void
    {
    }
}