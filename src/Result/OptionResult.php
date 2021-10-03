<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Result;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class OptionResult
{

	public string $name;

	public string $property;

	public ?string $description = null;
	
	public ?string $shortcut = null;

	public mixed $default;

	public string $type;

	public bool $allowsNull;
	
	public bool $argument = false;

	public function isset(InputInterface $input): bool
	{
		return $this->argument ? $input->hasArgument($this->name) : $input->hasOption($this->name);
	}

	public function get(InputInterface $input): mixed
	{
		return $this->argument ? $input->getArgument($this->name) : $input->getOption($this->name);
	}

	public function getDefault(): mixed
	{
		if ($this->type === 'bool') {
			return null;
		}
		
		return $this->default;
	}
	
	public function getOptionMode(): int
	{
		if ($this->type === 'bool') {
			$mode = InputOption::VALUE_NONE;
		} else {
			$mode = InputOption::VALUE_REQUIRED;
		}

		if ($this->type === 'array') {
			$mode |= InputOption::VALUE_IS_ARRAY;
		}

		return $mode;
	}
	
	public function getArgumentMode(): int
	{
		if (!$this->allowsNull) {
			$mode = InputArgument::REQUIRED;
		} else {
			$mode = InputArgument::OPTIONAL;
		}
		
		if ($this->type === 'array') {
			$mode |= InputArgument::IS_ARRAY;
		}

		return $mode;
	}

}
