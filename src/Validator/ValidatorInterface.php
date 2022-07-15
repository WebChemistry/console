<?php declare(strict_types = 1);

namespace WebChemistry\Console\Validator;

use WebChemistry\Console\Exceptions\ValidationException;

interface ValidatorInterface
{

	/**
	 * @throws ValidationException
	 */
	public function validate(object $object): void;

}
