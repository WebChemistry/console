<?php declare(strict_types = 1);

namespace WebChemistry\Console\Traits;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use WebChemistry\Console\TerminateCommandException;

trait ConfirmToContinueMethod
{

	protected function confirmToContinue(bool $default = false, string $message = 'Continue with this action?'): void
	{
		$helper = $this->getHelper('question');
		$defaultTxt = $this->output->getFormatter()->format(sprintf('<comment>[%s]</comment>', $default ? 'yes' : 'no'));

		$returned = $helper->ask(
			$this->input,
			$this->output,
			new ConfirmationQuestion(
				sprintf('%s %s ', $message, $defaultTxt),
				$default
			),
		);

		if (!$returned) {
			throw new TerminateCommandException(true);
		}
	}

}
