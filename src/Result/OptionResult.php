<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Result;

final class OptionResult
{

	public string $name;

	public string $property;

	public ?string $description = null;

	public mixed $default;

	public string $type;

	public bool $allowsNull;

}
