<?php

namespace ICanBoogie\Operation\Modules\Sample;

class SuccessWithLocationOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		$this->response->location = '/a/new/location';

		return true;
	}
}