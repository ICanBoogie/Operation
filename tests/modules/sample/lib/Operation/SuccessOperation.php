<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

class SuccessOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		return true;
	}
}
