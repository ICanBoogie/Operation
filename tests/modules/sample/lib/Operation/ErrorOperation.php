<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class ErrorOperation extends Operation
{
	protected function validate(Errors $errors)
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
