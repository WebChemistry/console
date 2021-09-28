<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Traits;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

trait WarningMethod
{

	public function warning(string $message): void
	{
		if (!$this->output->getFormatter()->hasStyle('warning')) {
			$this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('bright-yellow'));
		}

		$this->output->writeln(sprintf('<warning>Warning: %s</warning>', $message));
	}

}
