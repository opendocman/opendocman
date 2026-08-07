<?php

class ServerConfigChecker implements CheckerInterface
{
    private array $checks;

    public function __construct()
    {
        $self = $this;
        $this->checks = [
            'file_uploads' => [
                'severity' => 'required',
                'why' => 'File uploads must be enabled',
                'pass' => fn($val) => $val === '1',
                'format' => fn($val) => $val === '1' ? 'On' : 'Off',
            ],
            'upload_max_filesize' => [
                'severity' => 'recommended',
                'why' => 'At least 8M recommended for document uploads',
                'pass' => fn($val) => $self->compareSize($val, '8M'),
                'format' => fn($val) => $val,
            ],
            'post_max_size' => [
                'severity' => 'recommended',
                'why' => 'Should be >= upload_max_filesize',
                'pass' => fn($val) => $self->compareSize($val, '8M'),
                'format' => fn($val) => $val,
            ],
            'memory_limit' => [
                'severity' => 'recommended',
                'why' => 'At least 64M recommended for PDF processing',
                'pass' => fn($val) => $val === '-1' || $self->compareSize($val, '64M'),
                'format' => fn($val) => $val,
            ],
            'max_execution_time' => [
                'severity' => 'recommended',
                'why' => 'At least 30 seconds recommended for PDF parsing',
                'pass' => fn($val) => (int)$val === 0 || (int)$val >= 30,
                'format' => fn($val) => $val . 's',
            ],
            'display_errors' => [
                'severity' => 'recommended',
                'why' => 'Should be Off in production',
                'pass' => fn($val) => $val !== '1',
                'format' => fn($val) => $val === '1' ? 'On' : 'Off',
            ],
        ];
    }

    public function getName(): string
    {
        return 'Server Configuration';
    }

    public function check(): array
    {
        $results = [];
        foreach ($this->checks as $name => $config) {
            $value = ini_get($name);
            $passed = $config['pass']($value);
            $results[] = new CheckResult(
                $name,
                $config['why'],
                $config['format']($value),
                $passed,
                $config['severity']
            );
        }
        return $results;
    }

    private function compareSize(string $ini, string $threshold): bool
    {
        return $this->parseBytes($ini) >= $this->parseBytes($threshold);
    }

    private function parseBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $num = (int) $value;
        return match ($unit) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }
}