<?php declare(strict_types = 1);

namespace WebChemistry\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Shortcut
{

	public function __construct(
		public ?string $shortcut = null,
	)
	{
	}

}
