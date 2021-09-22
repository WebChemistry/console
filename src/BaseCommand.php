<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments;

use LogicException;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{

	private ConsoleObjectConfigurationParser $_parser;

	protected OutputInterface $output;

	protected InputInterface $input;

	private function getParser(): ConsoleObjectConfigurationParser
	{
		return $this->_parser ??= new ConsoleObjectConfigurationParser($this->getClassName());
	}

	private function getClassName(): string
	{
		if (!property_exists($this, 'arguments')) {
			throw new LogicException(sprintf('Class %s must have property $arguments.', static::class));
		}

		$reflection = new ReflectionProperty(static::class, 'arguments');
		if ($reflection->isPrivate()) {
			throw new LogicException(sprintf('%s::$arguments must not be private.', static::class));
		}

		$type = $reflection->getType();

		if (!$type instanceof ReflectionNamedType) {
			throw new LogicException(sprintf('%s::$arguments must be typed.', static::class));
		}

		return $type->getName();
	}

	final protected function configure()
	{
		$this->getParser()->configure($this);
	}

	protected function startup(): void
	{
	}

	final protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$arguments = $this->getParser()->hydrate($input, $output);
		if (!$arguments) {
			return self::FAILURE;
		}

		$reflection = new ReflectionProperty($this, 'arguments');
		$reflection->setAccessible(true);
		$reflection->setValue($this, $arguments);

		$this->input = $input;
		$this->output = $output;

		$this->startup();

		try {
			$this->exec();
		} catch (TerminateCommandException $e) {
			return $e->success ? self::SUCCESS : self::FAILURE;
		} finally {
			unset($this->input, $this->output);
		}

		return self::SUCCESS;
	}

	protected function error(string $message): void
	{
		$this->output->writeln(sprintf('<error>Error: %s</error>', $message));

		throw new TerminateCommandException();
	}

	abstract protected function exec();

}
