<?php

class ComposerDependencyChecker implements CheckerInterface
{
    private array $packages = [
        'Smalot\PdfParser\Parser'                       => 'PDF text extraction',
        'ParagonIE\AntiCSRF\AntiCSRF'                   => 'CSRF protection',
        'League\MimeTypeDetection\FinfoMimeTypeDetector' => 'MIME type detection',
        'Aura\Html\HelperLocatorFactory'                 => 'View rendering helpers',
    ];

    public function getName(): string
    {
        return 'Composer Dependencies';
    }

    public function check(): array
    {
        $results = [];

        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        $autoloadExists = file_exists($autoloadPath);
        $results[] = new CheckResult(
            'Composer Autoloader',
            'vendor/autoload.php must exist',
            $autoloadExists ? 'Found' : 'Missing — run composer install --no-dev',
            $autoloadExists,
            'required'
        );

        if (!$autoloadExists) {
            foreach ($this->packages as $class => $purpose) {
                $shortName = substr($class, strrpos($class, '\\') + 1);
                $results[] = new CheckResult(
                    "Composer Package: {$shortName}",
                    $purpose,
                    'Skipped (autoloader missing)',
                    false,
                    'required'
                );
            }
            return $results;
        }

        foreach ($this->packages as $class => $purpose) {
            $shortName = substr($class, strrpos($class, '\\') + 1);
            $found = class_exists($class);
            $results[] = new CheckResult(
                "Composer Package: {$shortName}",
                $purpose,
                $found ? 'Available' : 'Missing',
                $found,
                'required'
            );
        }

        return $results;
    }
}