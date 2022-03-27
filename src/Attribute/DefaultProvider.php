<?php declare(strict_types = 1);

namespace WebChemistry\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class DefaultProvider
{
	
	public function __construct(
		public string $method,
	)
	{
	}

}
