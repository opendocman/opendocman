<?php

class ConfigManager
{
    private string $configPath = '';
    private string $samplePath = '';
    private array $configPaths = [];

    public function __construct()
    {
        $this->configPaths = [
            __DIR__ . '/../configs/config.php',
            __DIR__ . '/../configs/docker-configs/config.php',
            __DIR__ . '/../../configs/config.php',
            __DIR__ . '/../../../configs/config.php',
        ];
        $this->samplePath = __DIR__ . '/../configs/config-sample.php';
    }

    public function configExists(): bool
    {
        return $this->discoverConfigPath() !== '';
    }

    public function discoverConfigPath(): string
    {
        if ($this->configPath !== '') {
            return $this->configPath;
        }
        foreach ($this->configPaths as $path) {
            if (file_exists($path)) {
                $this->configPath = $path;
                return $path;
            }
        }
        return '';
    }

    public function getSamplePath(): string
    {
        return $this->samplePath;
    }

    public function getEnvVar(string $name, ?string $default = null): ?string
    {
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }
        return $default;
    }

    public function writeConfig(array $params): void
    {
        if (!file_exists($this->samplePath)) {
            throw new RuntimeException('config-sample.php not found at: ' . $this->samplePath);
        }

        $sample = file_get_contents($this->samplePath);

        $replacements = [
            'database_name_here' => $params['db_name'],
            'username_here' => $params['db_user'],
            'password_here' => $params['db_pass'],
            'localhost' => $params['db_host'],
            "'odm_'" => "'" . $params['db_prefix'] . "'",
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $sample);

        $configsDir = dirname(__DIR__) . '/configs';
        $isDocker = $this->getEnvVar('IS_DOCKER') === 'true';
        if ($isDocker) {
            $targetDir = $configsDir . '/docker-configs/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        } else {
            $targetDir = $configsDir . '/';
        }

        $targetFile = $targetDir . 'config.php';
        if (file_put_contents($targetFile, $content) === false) {
            throw new RuntimeException('Failed to write config file to: ' . $targetFile);
        }

        $this->configPath = $targetFile;
    }

    public function loadConfig(): void
    {
        $path = $this->discoverConfigPath();
        if ($path !== '') {
            require $path;
        }
    }

    public function getTargetPath(): string
    {
        $configsDir = dirname(__DIR__) . '/configs';
        $isDocker = $this->getEnvVar('IS_DOCKER') === 'true';
        return $isDocker
            ? $configsDir . '/docker-configs/config.php'
            : $configsDir . '/config.php';
    }
}