<?php

class CheckResult
{
    public string $name;
    public string $required;
    public string $actual;
    public bool $passed;
    public string $severity; // 'required' | 'recommended'

    public function __construct(
        string $name,
        string $required,
        string $actual,
        bool $passed,
        string $severity = 'required'
    ) {
        $this->name = $name;
        $this->required = $required;
        $this->actual = $actual;
        $this->passed = $passed;
        $this->severity = $severity;
    }

    public function isRequired(): bool
    {
        return $this->severity === 'required';
    }
}