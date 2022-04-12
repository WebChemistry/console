<?php declare(strict_types = 1);

namespace WebChemistry\Console\Util\Result;

final class StringResult
{

	public function __construct(
		public int $code,
		public string $result,
		public bool $success,
	)
	{
	}

}
