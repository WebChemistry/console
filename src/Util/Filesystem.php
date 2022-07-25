<?php declare(strict_types = 1);

namespace WebChemistry\Console\Util;

use LogicException;

final class Filesystem
{

	public static function normalizePath(string $path): string
	{
		if (str_contains($path, '~')) {
			$path = strtr($path, ['~' => self::getHomeDir()]);
		}

		return \Nette\Utils\FileSystem::normalizePath($path);
	}

	public static function getHomeDir(): string
	{
		$directory = getenv('HOME');

		return is_string($directory) ? $directory : throw new LogicException('Cannot get home directory.');
	}

	public static function getCurrentDir(): string
	{
		$working = getcwd();

		return $working === false ? throw new LogicException('Cannot get current working dir.') : $working;
	}

}
