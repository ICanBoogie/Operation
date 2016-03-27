<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\ErrorCollection;
use ICanBoogie\Operation;

class SuccessOperation extends Operation
{
	protected function validate(ErrorCollection $errors)
	{
		return true;
	}

	protected function process()
	{
		return true;
	}
}
