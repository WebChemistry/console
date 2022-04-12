<?php declare(strict_types = 1);

namespace WebChemistry\Console\Util\Result;

final class ArrayResult
{

	/**
	 * @param string[] $result
	 */
	public function __construct(
		public int $code,
		public array $result,
		public bool $success,
	)
	{
	}

}
