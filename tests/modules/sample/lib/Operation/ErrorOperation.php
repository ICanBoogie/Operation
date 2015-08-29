<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

class ErrorOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
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
