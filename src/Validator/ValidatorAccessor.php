<?php declare(strict_types = 1);

namespace WebChemistry\ConsoleArguments\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WebChemistry\ConsoleArguments\Exceptions\ValidationException;

final class ValidatorAccessor
{

	private ValidatorInterface $validator;

	public function __construct(?ValidatorInterface $validator = null)
	{
		$this->validator = $validator ?? $this->createValidator();
	}

	public function getValidator(): ValidatorInterface
	{
		return $this->validator;
	}

	public function validate(object $object): ?ConstraintViolationListInterface
	{
		$errors = $this->validator->validate($object);
		if (!$errors->count()) {
			return null;
		}
		
		return $errors;
	}

	public function validateToString(object $object): ?string
	{
		$errors = $this->validator->validate($object);
		if (!$errors->count()) {
			return null;
		}

		$message = '';
		/** @var ConstraintViolation $error */
		foreach ($errors as $error) {
			$message .= sprintf("%s: %s\n", $error->getPropertyPath(), $error->getMessage());
		}

		return substr($message, 0, -1);
	}

	public function validateThrowOnError(object $object): void
	{
		if ($errors = $this->validateToString($object)) {
			throw new ValidationException($errors);
		}
	}

	private function createValidator(): ValidatorInterface
	{
		return Validation::createValidatorBuilder()
			->enableAnnotationMapping(true)
			->getValidator();
	}

}
