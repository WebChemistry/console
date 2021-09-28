<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Traits;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use WebChemistry\ConsoleArguments\TerminateCommandException;

trait ConfirmToContinueMethod
{

	protected function confirmToContinue(bool $default = false): void
	{
		$helper = $this->getHelper('question');
		$defaultTxt = $this->output->getFormatter()->format(sprintf('<comment>[%s]</comment>', $default ? 'yes' : 'no'));

		$returned = $helper->ask(
			$this->input,
			$this->output,
			new ConfirmationQuestion(
				sprintf('%s %s ', 'Continue with this action?', $defaultTxt),
				$default
			),
		);

		if (!$returned) {
			throw new TerminateCommandException(true);
		}
	}

}
