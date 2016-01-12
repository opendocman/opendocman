<?php

namespace Phplint\Command;

use Phplint\Linter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LintCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('lint')
            ->setDescription('Lint something')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to a php file or directory to lint'
            )
            ->addOption(
               'exclude',
               null,
               InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
               'Path to a php file or directory to exclude from linting'
            )
            ->addOption(
               'extensions',
               null,
               InputOption::VALUE_REQUIRED,
               'Check only files with selected extensions (default: php)'
            )
            ->addOption(
               'jobs',
               'j',
               InputOption::VALUE_REQUIRED,
               'Number of parraled jobs to run (default: 5)'
            )
            // ->addOption(
            //    'fail-on-first',
            //    null,
            //    InputOption::VALUE_NONE,
            //    'Exit with non zero code on first lint error'
            // )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        $output->writeln("phplint {$this->getApplication()->getVersion()}");
        $output->writeln('');

        $phpBinary  = PHP_BINARY;
        $path       = $input->getArgument('path');
        $exclude    = $input->getOption('exclude');
        $extensions = $input->getOption('extensions');
        $procLimit  = $input->getOption('jobs');
        // $failOnFirst = $input->getOption('fail-on-first');

        if ($extensions) {
            $extensions = explode(',', $extensions);
        } else {
            $extensions = array('php');
        }

        $linter = new Linter($path, $exclude, $extensions);
        if ($procLimit) {
            $linter->setProcessLimit($procLimit);
        }

        $files     = $linter->getFiles();
        $fileCount = count($files);

        $progress = new ProgressBar($output, $fileCount);
        $progress->setBarWidth(50);
        $progress->setMessage('', 'overview');
        $progress->setFormat(" %overview%\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%");
        $progress->start();

        $linter->setProcessCallback(function ($status, $filename) use ($progress) {
            /*
            $overview = $progress->getMessage('overview');

            if ($status == 'ok') {
                $overview .= '.';
            } elseif ($status == 'error') {
                $overview .= 'F';
                // exit(1);
            }

            $progress->setMessage($overview, 'overview');
            */
            $progress->advance();
        });

        $result = $linter->lint($files);

        $progress->finish();
        $output->writeln('');

        $testTime = microtime(true) - $startTime;

        $code = 0;
        $errCount = count($result);
        $out = "<info>Checked {$fileCount} files in ".round($testTime, 1)." seconds</info>";
        if ($errCount > 0) {
            $out .= "<info> and found syntax errors in </info><error>{$errCount}</error><info> files.</info>";
            $out .= "\n" . json_encode($result, JSON_PRETTY_PRINT);
            $code = 1;
        } else {
            $out .= '<info> a no syntax error were deteced.';
        }
        $output->writeln($out.PHP_EOL);

        return $code;
    }
}
