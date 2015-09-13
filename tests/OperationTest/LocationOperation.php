<?php

namespace ICanBoogie\Operation\OperationTest;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class LocationOperation extends Operation
{
	public function validate(Errors $errors)
	{
		return true;
	}

	public function process()
	{
		$this->response->location = '/path/to/redirection/';

		return true;
	}
}
