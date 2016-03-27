<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\ErrorCollection;
use ICanBoogie\Operation;

class SuccessWithLocationOperation extends Operation
{
	protected function validate(ErrorCollection $errors)
	{
		return true;
	}

	protected function process()
	{
		$this->response->location = '/a/new/location';

		return true;
	}
}
