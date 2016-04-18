<?php

namespace Phplint;

use Symfony\Component\Finder\Finder;
use Phplint\Process\Lint;

class Linter
{
    /** @var callable */
    private $processCallback;

    private $path;
    private $excludes;
    private $extensions;

    private $procLimit = 5;

    public function __construct($path, $excludes, $extensions)
    {
        $this->path       = $path;
        $this->excludes   = $excludes;
        $this->extensions = $extensions;
    }

    public function lint($files)
    {
        $processCallback = is_callable($this->processCallback) ? $this->processCallback : function() {};

        $running = array();
        $errors  = array();
        while ($files || $running) {
            for ($i = count($running); $files && $i < $this->procLimit; $i++) {
                $file     = array_shift($files);
                $fileName = $file->getRealpath();

                $running[$fileName] = new Lint(PHP_BINARY.' -l '.$fileName);
                $running[$fileName]->start();
            }

            foreach ($running as $fileName => $lintProcess) {
                if (!$lintProcess->isRunning()) {
                    unset($running[$fileName]);
                    if ($lintProcess->hasSyntaxError()) {
                        $processCallback('error', $fileName);
                        $errors[$fileName] = $lintProcess->getSyntaxError();
                    } else {
                        $processCallback('ok', $file);
                    }
                }
            }
        }

        return $errors;
    }

    public function getFiles()
    {
        $files = new Finder();
        $files->files()->in(realpath($this->path));

        foreach ($this->excludes as $exclude) {
            $files->notPath($exclude);
        }

        foreach ($this->extensions as $extension) {
            $files->name('*.'.$extension);
        }

        return iterator_to_array($files);
    }

    /**
     * @param callable $processCallback
     * @return ParallelLint
     */
    public function setProcessCallback($processCallback)
    {
        $this->processCallback = $processCallback;

        return $this;
    }

    public function setProcessLimit($procLimit)
    {
        $this->procLimit = $procLimit;

        return $this;
    }
}
