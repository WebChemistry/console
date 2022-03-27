<?php declare(strict_types = 1);

namespace WebChemistry\Console\Extension;

interface DefaultValuesProviderInterface
{

	public function provideDefaultValues(): iterable;

}
