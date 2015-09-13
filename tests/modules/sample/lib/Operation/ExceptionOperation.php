<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;
use ICanBoogie\Operation\Modules\Sample\SampleException;

class ExceptionOperation extends Operation
{
	protected function validate(Errors $errors)
	{
		throw new SampleException('My Exception Message.', 500);
	}

	protected function process()
	{
		return true;
	}
}
