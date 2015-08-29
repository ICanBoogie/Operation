<?php

namespace ICanBoogie\Operation\Modules\Sample\Operation;

use ICanBoogie\Operation\FormHasExpired;

class ExpiredOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
	{
		throw new FormHasExpired;
	}

	protected function process()
	{
		return true;
	}
}
