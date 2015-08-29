<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Operation\Modules\Sample\SampleException;

class ExceptionOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
	{
		throw new SampleException('My Exception Message.', 500);
	}

	protected function process()
	{
		return true;
	}
}
