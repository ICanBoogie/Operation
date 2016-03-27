<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\ErrorCollection;
use ICanBoogie\Operation;

class ErrorOperation extends Operation
{
	protected function validate(ErrorCollection $errors)
	{
		$errors['one'] = 'One error.';
		$errors[] = "General error.";

		return false;
	}

	protected function process()
	{
		return true;
	}
}
