<?php

class PhpExtensionChecker implements CheckerInterface
{
    private array $extensions = [
        'zip'      => ['severity' => 'required',    'why' => 'DOCX/XLSX/PPTX/ODT extraction'],
        'dom'      => ['severity' => 'required',    'why' => 'XML parsing in document extractors'],
        'xml'      => ['severity' => 'required',    'why' => 'XML support'],
        'mbstring' => ['severity' => 'required',    'why' => 'Multibyte text processing'],
        'fileinfo' => ['severity' => 'required',    'why' => 'MIME type detection'],
        'gd'       => ['severity' => 'recommended', 'why' => 'Image thumbnails'],
    ];

    public function getName(): string
    {
        return 'PHP Extensions';
    }

    public function check(): array
    {
        $results = [];
        foreach ($this->extensions as $ext => $config) {
            $loaded = extension_loaded($ext);
            $results[] = new CheckResult(
                "PHP Extension: {$ext}",
                $config['why'],
                $loaded ? 'Loaded' : 'Missing',
                $loaded,
                $config['severity']
            );
        }
        return $results;
    }
}