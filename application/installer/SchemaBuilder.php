<?php

class SchemaBuilder
{
    public function getVersion(): string
    {
        return ODM_DB_VERSION;
    }

    public function getCreateTableStatements(string $prefix): array
    {
        return [
            "CREATE TABLE `{$prefix}access_log` (
                `file_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                `action` enum('A','B','C','V','D','M','X','I','O','Y','R','U') NOT NULL
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}admin` (
                id int(11) unsigned default NULL,
                admin tinyint(4) default NULL
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}category` (
                id int(11) unsigned NOT NULL auto_increment,
                name varchar(255) NOT NULL default '',
                PRIMARY KEY  (id),
                UNIQUE (name(200))
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}category_perms` (
                cat_id int(11) unsigned NOT NULL,
                dept_id int(11) unsigned default NULL,
                user_id int(11) unsigned default NULL,
                rights tinyint(4) NOT NULL default '0',
                PRIMARY KEY (cat_id, dept_id, user_id),
                KEY cat_id (cat_id),
                KEY dept_id (dept_id),
                KEY user_id (user_id)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}data` (
                id int(11) unsigned NOT NULL auto_increment,
                category int(11) unsigned NOT NULL default '0',
                owner int(11) unsigned default NULL,
                realname varchar(255) NOT NULL default '',
                created datetime NOT NULL,
                description varchar(255) default NULL,
                comment varchar(255) default '',
                status smallint(6) default NULL,
                department smallint(6) unsigned default NULL,
                default_rights tinyint(4) default NULL,
                publishable tinyint(4) default NULL,
                is_public tinyint(1) DEFAULT 0,
                reviewer int(11) unsigned default NULL,
                reviewer_comments varchar(255) default NULL,
                PRIMARY KEY  (id),
                KEY data_idx (id,owner),
                KEY id (id),
                KEY id_2 (id),
                KEY publishable (publishable),
                KEY description (description(200))
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}department` (
                id int(11) unsigned NOT NULL auto_increment,
                name varchar(255) NOT NULL default '',
                PRIMARY KEY  (id)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}dept_perms` (
                fid int(11) unsigned default NULL,
                dept_id int(11) unsigned default NULL,
                rights tinyint(4) NOT NULL default '0',
                KEY rights (rights),
                KEY dept_id (dept_id),
                KEY fid (fid)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}dept_reviewer` (
                dept_id int(11) unsigned default NULL,
                user_id int(11) unsigned default NULL
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}log` (
                id int(11) unsigned NOT NULL default '0',
                modified_on datetime NOT NULL,
                modified_by varchar(25) default NULL,
                note text,
                revision varchar(255) default NULL,
                KEY id (id),
                KEY modified_on (modified_on)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}rights` (
                RightId tinyint(4) default NULL,
                Description varchar(255) default NULL
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}user` (
                id int(11) unsigned NOT NULL auto_increment,
                username varchar(25) NOT NULL default '',
                password varchar(50) NOT NULL default '',
                department int(11) unsigned default NULL,
                phone varchar(20) default NULL,
                Email varchar(50) default NULL,
                last_name varchar(255) default NULL,
                first_name varchar(255) default NULL,
                pw_reset_code char(32) default NULL,
                pw_change_required tinyint(1) NOT NULL DEFAULT 0,
                can_add tinyint(1) NULL DEFAULT 1,
                can_checkin tinyint(1) NULL DEFAULT 1,
                PRIMARY KEY  (id)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}user_perms` (
                fid int(11) unsigned default NULL,
                uid int(11) unsigned NOT NULL default '0',
                rights tinyint(4) NOT NULL default '0',
                KEY user_perms_idx (fid,uid,rights),
                KEY fid (fid),
                KEY uid (uid),
                KEY rights (rights)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}udf` (
                id  int auto_increment unique,
                table_name varchar(50),
                display_name varchar(16),
                field_type int
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}odmsys` (
                id  int(11) auto_increment unique,
                sys_name  varchar(16),
                sys_value    varchar(255)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}settings` (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                value VARCHAR(255) NOT NULL,
                description VARCHAR(255) NOT NULL,
                validation VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE (name(200))
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}filetypes` (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                type VARCHAR(255) NOT NULL,
                active TINYINT(4) NOT NULL,
                PRIMARY KEY (id)
            ) ENGINE = MYISAM",

            "CREATE TABLE `{$prefix}content_index` (
                `file_id` int(11) unsigned NOT NULL,
                `content_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
                `indexed_at` datetime DEFAULT NULL,
                PRIMARY KEY (`file_id`),
                FULLTEXT INDEX `ft_content` (`content_text`)
            ) ENGINE = MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
    }

    public function getDefaultDataStatements(string $prefix, array $options = []): array
    {
        $adminPassword = $options['admin_password'] ?? 'admin';
        $dataDir = $options['datadir'] ?? '/var/www/document_repository/';
        $snapshotDir = $options['snapshotdir'] ?? '/var/www/snapshots/';

        return [
            "INSERT INTO `{$prefix}admin` VALUES (1,1)",
            "INSERT INTO `{$prefix}category` VALUES (NULL,'SOP')",
            "INSERT INTO `{$prefix}category` VALUES (NULL,'Training Manual')",
            "INSERT INTO `{$prefix}category` VALUES (NULL,'Letter')",
            "INSERT INTO `{$prefix}category` VALUES (NULL,'Presentation')",
            "INSERT INTO `{$prefix}department` VALUES (NULL,'Information Systems')",
            "INSERT INTO `{$prefix}dept_reviewer` VALUES (1,1)",
            "INSERT INTO `{$prefix}rights` VALUES (0,'Unset')",
            "INSERT INTO `{$prefix}rights` VALUES (1,'view')",
            "INSERT INTO `{$prefix}rights` VALUES (-1,'forbidden')",
            "INSERT INTO `{$prefix}rights` VALUES (2,'read')",
            "INSERT INTO `{$prefix}rights` VALUES (3,'write')",
            "INSERT INTO `{$prefix}rights` VALUES (4,'admin')",
            "INSERT INTO `{$prefix}user` VALUES (NULL,'admin','" . password_hash($adminPassword, PASSWORD_DEFAULT) . "','1','5555551212','admin@example.com','User','Admin','',0,1,1)",
            "INSERT INTO `{$prefix}odmsys` VALUES (NULL,'version','{$this->getVersion()}')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'debug', 'False', '(True/False) - Default=False - Debug the installation (not working)', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'demo', 'False', '(True/False) This setting is for a demo installation, where random people will be all loggging in as the same username/password like \"demo/demo\". This will keep users from removing files, users, etc.', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'authen', 'mysql', '(Default = mysql) Currently only MySQL authentication is supported', '')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'title', 'Document Repository', 'This is the browser window title', 'maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'site_mail', 'root@localhost', 'The email address of the administrator of this site', 'email|maxsize=255|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'root_id', '1', 'This variable sets the root user id.  The root user will be able to access all files and have authority for everything.', 'num|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'dataDir', '{$dataDir}', 'location of file repository. This should ideally be outside the Web server root. Make sure the server has permissions to read/write files to this folder!. (Examples: Linux - /var/www/document_repository/ : Windows - c:/document_repository/', 'maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'snapshotDir', '{$snapshotDir}', 'Location to store database and file snapshots. Should be outside web root.', 'maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'max_filesize', '5000000', 'Set the maximum file upload size', 'num|maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'revision_expiration', '90', 'This var sets the amount of days until each file needs to be revised,  assuming that there are 30 days in a month for all months.', 'num|maxsize=255')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'file_expired_action', '1', 'Choose an action option when a file is found to be expired The first two options also result in sending email to reviewer  (1) Remove from file list until renewed (2) Show in file list but non-checkoutable (3) Send email to reviewer only (4) Do Nothing', 'num')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'public_sharing', 'False', '(True/False) Enable public file sharing page. When enabled, files marked as public and approved will be visible without authentication.', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'authorization', 'True', 'True or False. If set True, every document must be reviewed by an admin before it can go public. To disable set to False. If False, all newly added/checked-in documents will immediately be listed', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'allow_signup', 'False', 'Should we display the sign-up link?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'allow_password_reset', 'False', 'Should we allow users to reset their forgotten password?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'try_nis', 'False', 'Attempt NIS password lookups from YP server?', 'bool')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'theme', 'bootstrap5', 'Which theme to use?', '')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'language', 'english', 'Set the default language (english, spanish, turkish, etc.). Local users may override this setting. Check include/language folder for languages available', 'alpha|req')",
            "INSERT INTO `{$prefix}settings` VALUES(NULL, 'max_query', '500', 'Set this to the maximum number of rows you want to be returned in a file listing. If your file list is slow decrease this value.', 'num')",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/gif', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/jpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/pjpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/png', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/webp', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/bmp', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/tiff', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/tif', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/svg+xml', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/x-dwg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'image/x-dfx', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'drawing/x-dwf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/pdf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-pdf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/msword', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.ms-excel', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.ms-powerpoint', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.ms-access', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/rtf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/rtf', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/plain', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/html', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/csv', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'text/xml', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/json', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-master', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.text-web', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.spreadsheet-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.presentation-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.graphics-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.chart-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.formula-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/vnd.oasis.opendocument.image-template', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/zip', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-zip-compressed', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-rar-compressed', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-7z-compressed', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-tar', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/gzip', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/x-bzip2', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'application/octet-stream', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'audio/mpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'audio/ogg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'audio/x-wav', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'audio/x-flac', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/mp4', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/mpeg', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/x-msvideo', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/x-ms-wmv', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/quicktime', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/webm', 1)",
            "INSERT INTO `{$prefix}filetypes` VALUES(NULL, 'video/3gpp', 1)",
        ];
    }

    public function buildFullDump(string $prefix, array $options = []): string
    {
        $lines = [];
        $lines[] = '# MySQL dump of OpenDocMan';
        $lines[] = '# Generated by SchemaBuilder';
        $lines[] = '#';

        foreach ($this->getCreateTableStatements($prefix) as $stmt) {
            $lines[] = '';
            $lines[] = $stmt . ';';
        }

        foreach ($this->getDefaultDataStatements($prefix, $options) as $stmt) {
            $lines[] = $stmt . ';';
        }

        return implode("\n", $lines) . "\n";
    }
}
