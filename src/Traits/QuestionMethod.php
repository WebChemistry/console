<?php declare(strict_types = 1);

namespace WebChemistry\Console\Traits;

use Symfony\Component\Console\Question\ConfirmationQuestion;

trait QuestionMethod
{

	public function question(string $question, bool $default = false): bool
	{
		$helper = $this->getHelper('question');
		$defaultTxt = $this->output->getFormatter()->format(
			sprintf('<comment>[%s]</comment>', $default ? 'yes' : 'no')
		);

		return $helper->ask(
			$this->input,
			$this->output,
			new ConfirmationQuestion(sprintf('%s %s ', $question, $defaultTxt), $default),
		);
	}

}
