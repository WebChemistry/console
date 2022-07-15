<?php declare(strict_types = 1);

namespace WebChemistry\Console;

use LogicException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;
use WebChemistry\Console\Attribute\Argument;
use WebChemistry\Console\Attribute\Configuration;
use WebChemistry\Console\Attribute\Description;
use WebChemistry\Console\Attribute\Shortcut;
use WebChemistry\Console\Exceptions\MismatchTypeException;
use WebChemistry\Console\Extension\DefaultValuesProviderInterface;
use WebChemistry\Console\Extension\ValidateObjectInterface;
use WebChemistry\Console\Result\CommandResult;
use WebChemistry\Console\Result\OptionResult;
use WebChemistry\Console\Validator\SymfonyValidator;
use WebChemistry\Console\Validator\ValidatorInterface;
use WebChemistry\Console\Validator\VoidValidator;

final class CommandArgumentsParser
{

	private CommandResult $commandResult;
	
	private ValidatorInterface $validator;
	
	private OutputInterface $output;
	
	private bool $earlyError = false;

	public function __construct(
		private string $className,
		?ValidatorInterface $validator = null,
	)
	{
		$this->validator = $validator ?? $this->createValidator();
	}

	private function createValidator(): ValidatorInterface
	{
		return interface_exists(SymfonyValidatorInterface::class) ? new SymfonyValidator() : new VoidValidator();
	}

	public function hydrate(InputInterface $input, OutputInterface $output): ?object
	{
		$reflection = new ReflectionClass($this->className);
		
		$this->output = $output;
		$this->earlyError = false;
		
		$object = $reflection->newInstanceWithoutConstructor();
		$result = $this->getCommandResult();

		try {
			$arguments = (new Processor())->process(
				Expect::from($object, $this->createSchema($input)),
				$this->getValues($input),
			);
		} catch (ValidationException $exception) {
			foreach ($exception->getMessageObjects() as $object) {
				$name = $object->path[0];
				$option = $result->options[$name];
				
				$this->printTypeError(
					$option->argument,
					$option->name,
					$object->variables['expected'],
					get_debug_type($object->variables['value'])
				);
			}

			return null;
		}
		
		if ($this->earlyError) {
			return null;
		}

		if ($arguments instanceof DefaultValuesProviderInterface) {
			$this->processDefaultValues($arguments, $arguments->provideDefaultValues());
		}

		if ($arguments instanceof ValidateObjectInterface) {
			$arguments->validate();
		}

		$this->validator->validate($arguments);

		return $arguments;
	}

	private function printTypeError(bool $isArgument, string $name, string $expected, string $given): void
	{
		$this->output->writeln(
			sprintf(
				'<error>%s%s expects to be "%s", "%s" given.</error>',
				$isArgument ? 'Argument ' : 'Option --',
				$name,
				$expected,
				$given,
			)
		);
	}

	public function configure(Command $command): void
	{
		$result = $this->getCommandResult();

		$command->setDescription((string) ($result->description ?? $this->getDescription(new ReflectionClass($command))));

		foreach ($result->options as $option) {
			if (!$option->argument) {
				$command->addOption(
					$option->name,
					$option->shortcut,
					$option->getOptionMode(),
					(string) $option->description,
					$option->getDefault(),
				);
			} else {
				$command->addArgument(
					$option->name,
					$option->getArgumentMode(),
					(string) $option->description,
					$option->getDefault(),
				);
			}
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
			if ($option->isset($input)) {
				try {
					$values[$option->property] = $option->get($input);
				} catch (MismatchTypeException $exception) {
					$this->printTypeError(
						$option->argument,
						$option->name,
						$exception->expected,
						$exception->given,
					);
					
					$this->earlyError = true;
				}
			}
		}

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
			if (!$option->isset($input)) {
				continue;
			}

			if ($option->type === 'int') {
				$type = Expect::type('numericint')->castTo('int');

				if ($option->allowsNull && $option->get($input) === null) {
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
		$commandResult->reflection = $reflection;
		$commandResult->description = $this->getDescription($reflection);

		foreach ($reflection->getProperties() as $property) {
			if (!$property->isPublic()) {
				continue;
			}

			if (str_starts_with($property->getName(), '_')) {
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

			$option = $commandResult->options[$property->getName()] = new OptionResult();
			$option->name = preg_replace_callback(
				'#([A-Z])#',
				fn (array $matches) => '-' . strtolower($matches[1]),
				$property->getName(),
			);
			
			$option->property = $property->getName();
			$option->type = $type->getName();
			$option->allowsNull = $type->allowsNull();
			$option->default = $property->hasDefaultValue() ? $property->getDefaultValue() : null;
			
			foreach ($property->getAttributes() as $attribute) {
				$instance = $attribute->newInstance();
				
				if ($instance instanceof Description) {
					$option->description = $instance->description;
				} elseif ($instance instanceof Argument) {
					$option->argument = true;
				} elseif ($instance instanceof Shortcut) {
					$option->shortcut = $instance->shortcut;
				} elseif ($instance instanceof Configuration) {
					$option->getter = $this->createCallback($instance->getterCallback, $instance);
					
					if (!$option->description) {
						$callback = $this->createCallback($instance->descriptionCallback, $instance);
						
						if ($callback) {
							$option->description = $callback();	
						}
					}
				}
			}
		}

		return $commandResult;
	}

	private function createCallback(?string $callback, Configuration $instance): ?callable
	{
		if (!$callback) {
			return null;
		}
		
		if (str_contains($callback, '::')) {
			return $callback;
		} else {
			$class = new ReflectionClass($instance);
			$method = $class->getMethod($callback);

			return $method->getClosure($instance);
		}
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

	private function processDefaultValues(object $object, iterable $defaults): void
	{
		$command = $this->getCommandResult();

		foreach ($defaults as $key => $value) {
			$property = $command->reflection->getProperty($key);

			if ($property->isPublic() && $property->getDefaultValue() === $property->getValue($object)) {
				$property->setValue($object, $value);
			}
		}
	}

}
