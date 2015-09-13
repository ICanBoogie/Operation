<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Errors;
use ICanBoogie\Operation;
use ICanBoogie\Operation\FormHasExpired;

class ExpiredOperation extends Operation
{
	protected function validate(Errors $errors)
	{
		throw new FormHasExpired;
	}

	protected function process()
	{
		return true;
	}
}
