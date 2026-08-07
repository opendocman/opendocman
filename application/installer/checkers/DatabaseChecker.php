<?php

class DatabaseChecker implements CheckerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getName(): string
    {
        return 'Database Server';
    }

    public function check(): array
    {
        $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $isMaria = stripos($version, 'MariaDB') !== false;
        $passed = false;

        if ($isMaria) {
            $numVersion = preg_replace('/^(\d+\.\d+).*$/', '$1', $version);
            $passed = version_compare($numVersion, '10.2', '>=');
        } else {
            $numVersion = preg_replace('/^(\d+\.\d+).*$/', '$1', $version);
            $passed = version_compare($numVersion, '5.7', '>=');
        }

        return [
            new CheckResult(
                'Database Server Version',
                $isMaria ? 'MariaDB 10.2+' : 'MySQL 5.7+',
                $version,
                $passed,
                'required'
            ),
        ];
    }
}