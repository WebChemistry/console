<?php declare(strict_types = 1);

namespace WebChemistry\Console\Util;

use Nette\Utils\Strings;
use WebChemistry\Console\Util\Result\ArrayResult;
use WebChemistry\Console\Util\Result\StreamResult;
use WebChemistry\Console\Util\Result\StringResult;

final class CommandLine
{

	private function __construct()
	{
	}

	public static function stream(string $command, string|int|float|null ... $arguments): StreamResult
	{
		$success = passthru(self::formatCommand($command, $arguments), $result) === null;

		return new StreamResult($result, $success);
	}

	public static function getArray(string $command, string|int|float|null ... $arguments): ArrayResult
	{
		exec(self::formatCommand($command, $arguments), $output, $resultCode);

		return new ArrayResult($resultCode, $output, $resultCode === 0);
	}

	public static function getString(string $command, string|int|float|null ... $arguments): StringResult
	{
		exec(self::formatCommand($command, $arguments), $output, $resultCode);

		return new StringResult($resultCode, implode("\n", $output), $resultCode === 0);
	}

	/**
	 * @param string $command
	 * @param array<array-key, string|int|float|null> $arguments
	 * @return string
	 */
	private static function formatCommand(string $command, array $arguments): string
	{
		return sprintf(
			strtr($command, ['%' => '%%', '??' => '?', '?' => '%s']),
			...array_map(fn (string|int|float|null $arg) => escapeshellarg((string) $arg), $arguments)
		);
	}

}
