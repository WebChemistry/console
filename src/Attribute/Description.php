<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_CLASS)]
final class Description
{

	public function __construct(
		public string $description,
	)
	{
	}

}
