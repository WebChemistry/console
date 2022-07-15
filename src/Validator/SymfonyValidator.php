<?php declare(strict_types = 1);

namespace WebChemistry\Console\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;
use WebChemistry\Console\Exceptions\ValidationException;

final class SymfonyValidator implements ValidatorInterface
{

	private SymfonyValidatorInterface $validator;

	public function __construct(?SymfonyValidatorInterface $validator = null)
	{
		$this->validator = $validator ?? $this->createValidator();
	}

	private function validateToString(object $object): ?string
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

	public function validate(object $object): void
	{
		if ($errors = $this->validateToString($object)) {
			throw new ValidationException($errors);
		}
	}

	private function createValidator(): SymfonyValidatorInterface
	{
		return Validation::createValidatorBuilder()
			->enableAnnotationMapping(true)
			->getValidator();
	}

}
