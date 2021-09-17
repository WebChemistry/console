<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments;

use WebChemistry\ConsoleArguments\Attribute\Argument;
use WebChemistry\ConsoleArguments\Attribute\Description;
use WebChemistry\ConsoleArguments\Result\CommandResult;
use WebChemistry\ConsoleArguments\Result\OptionResult;
use LogicException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleObjectConfigurationParser
{

	private CommandResult $commandResult;

	public function __construct(
		private string $className,
	)
	{
	}

	public function hydrate(InputInterface $input, OutputInterface $output): ?object
	{
		$object = (new ReflectionClass($this->className))->newInstanceWithoutConstructor();
		$result = $this->getCommandResult();

		try {
			$arguments = (new Processor())->process(
				Expect::from($object, $this->createSchema($input)),
				$this->getValues($input),
			);
		} catch (ValidationException $exception) {
			foreach ($exception->getMessageObjects() as $object) {
				$name = $object->path[0];
				$output->writeln(
					sprintf(
						'<error>Option --%s expects to be %s, %s given.</error>',
						$result->options[$name]->name,
						$object->variables['expected'],
						(string) $object->variables['value'],
					)
				);
			}

			return null;
		}

		if (method_exists($object, 'validate')) {
			$object->validate();
		}

		return $arguments;
	}

	public function configure(Command $command): void
	{
		$result = $this->getCommandResult();

		$command->setDescription((string) $result->description);

		foreach ($result->options as $option) {
			$default = $option->default;

			if ($option->type === 'bool') {
				$default = null;
				$mode = InputOption::VALUE_NONE;
			} else {
				$mode = InputOption::VALUE_REQUIRED;
			}

			if ($option->type === 'array') {
				$mode |= InputOption::VALUE_IS_ARRAY;
			}

			$command->addOption($option->name, null, $mode, (string) $option->description, $default);
		}
	}

	/**
	 * @return mixed[]
	 */
	private function getValues(InputInterface $input): array
	{
		$result = $this->getCommandResult();
		$values = [];

		foreach ($result->options as $option) {
			if ($input->hasOption($option->name)) {
				$values[$option->property] = $input->getOption($option->name);
			}
		}

//		foreach ($result->arguments as $argument) {
//			$values[$argument->property] = $input->getArgument($argument->name);
//		}

		return $values;
	}

	/**
	 * @return mixed[]
	 */
	private function createSchema(InputInterface $input): array
	{
		$schema = [];
		$result = $this->getCommandResult();

		foreach ($result->options as $option) {
			if (!$input->hasOption($option->name)) {
				continue;
			}

			if ($option->type === 'int') {
				$type = Expect::type('numericint')->castTo('int');

				if ($option->allowsNull && $input->getOption($option->name) === null) {
					// hack
					$type = Expect::type('null');
				}

				$schema[$option->property] = $type;
			}
		}

		return $schema;
	}

	private function getCommandResult(): CommandResult
	{
		return $this->commandResult ??= $this->parse();
	}

	private function parse(): CommandResult
	{
		$reflection = new ReflectionClass($this->className);

		$commandResult = new CommandResult();
		$commandResult->description = $this->getDescription($reflection);

		foreach ($reflection->getProperties() as $property) {
			if (!$property->isPublic()) {
				continue;
			}

			$type = $property->getType();
			if (!$type) {
				throw new LogicException(
					sprintf('Property %s::%s must have type.', $reflection->getName(), $property->getName())
				);
			}
			if ($type instanceof ReflectionUnionType) {
				throw new LogicException(
					sprintf('Property %s::%s must not be an union type.', $reflection->getName(), $property->getName())
				);
			}

			if (!$this->hasAttribute($property, Argument::class)) {
				$option = $commandResult->options[$property->getName()] = new OptionResult();
				$option->name = preg_replace_callback(
					'#([A-Z])#',
					fn (array $matches) => '-' . strtolower($matches[1]),
					$property->getName(),
				);
				$option->description = $this->getDescription($property);
				$option->property = $property->getName();
				$option->default = $property->hasDefaultValue() ? $property->getDefaultValue() : null;
				$option->type = $type->getName();
				$option->allowsNull = $type->allowsNull();
			}
		}

		return $commandResult;
	}

	private function getDescription(ReflectionClass|ReflectionProperty $reflection): ?string
	{
		return $this->getAttribute($reflection, Description::class)?->description;
	}

	/**
	 * @template T
	 * @param class-string<T> $attributeName
	 * @return T
	 */
	private function getAttribute(ReflectionClass|ReflectionProperty $reflection, string $attributeName): ?object
	{
		$attrs = $reflection->getAttributes($attributeName);

		return $attrs ? $attrs[0]->newInstance() : null;
	}

	private function hasAttribute(ReflectionClass|ReflectionProperty $reflection, string $attributeName): bool
	{
		return (bool) $reflection->getAttributes($attributeName);
	}

}
