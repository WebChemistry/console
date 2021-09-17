<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Result;

final class CommandResult
{

	public ?string $description = null;

	/** @var OptionResult[] */
	public array $options = [];

}
