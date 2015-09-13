<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class OnlineOperation extends Operation
{
	protected function validate(Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		return true;
	}
}
