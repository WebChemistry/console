<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments;

use Exception;
use Throwable;

final class TerminateCommandException extends Exception
{

	public function __construct(
		public bool $success = false,
	)
	{
		parent::__construct('Command terminated.', 0, null);
	}

}
