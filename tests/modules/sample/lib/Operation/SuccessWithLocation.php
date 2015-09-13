<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class SuccessWithLocationOperation extends Operation
{
	protected function validate(Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		$this->response->location = '/a/new/location';

		return true;
	}
}
