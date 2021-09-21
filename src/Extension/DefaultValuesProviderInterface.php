<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Extension;

interface DefaultValuesProviderInterface
{

	public function provideDefaultValues(): iterable;

}
