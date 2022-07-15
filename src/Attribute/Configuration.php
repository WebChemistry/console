<?php declare(strict_types = 1);

namespace WebChemistry\Console\Attribute;

use Attribute;

#[Attribute]
class Configuration
{
	
	public function __construct(
		public ?string $getterCallback = null,
		public ?string $descriptionCallback = null,
	)
	{
	}

}
