<?php

interface CheckerInterface
{
    /** @return CheckResult[] */
    public function check(): array;
    public function getName(): string;
}