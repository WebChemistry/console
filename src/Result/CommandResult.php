<?php declare(strict_types = 1);

namespace WebChemistry\Console\Result;

use ReflectionClass;

final class CommandResult
{

	public ?string $description = null;

	/** @var OptionResult[] */
	public array $options = [];

	public ReflectionClass $reflection;

}
