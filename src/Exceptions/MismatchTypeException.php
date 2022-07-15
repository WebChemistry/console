<?php declare(strict_types = 1);

namespace WebChemistry\Console\Exceptions;

final class MismatchTypeException extends \Exception
{

	public function __construct(
		public string $expected,
		public string $given,
	)
	{
		parent::__construct(sprintf('Variable expected to be "%s", "%s" given.', $expected, $given), $code, $previous);
	}

}
