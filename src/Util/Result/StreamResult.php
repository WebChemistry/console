<?php declare(strict_types = 1);

namespace WebChemistry\Console\Util\Result;

final class StreamResult
{

	public function __construct(
		public int $code,
		public bool $success,
	)
	{
	}

}
