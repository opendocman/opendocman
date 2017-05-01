<?php

namespace Phplint\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
	const VERSION = '0.0.1';

	public function getVersion()
	{
		return self::VERSION;
	}

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        // This should return the name of your command.
        return 'lint';
    }
}
