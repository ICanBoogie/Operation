<?php

namespace ICanBoogie\Operation\OperationTest;

use ICanBoogie\ErrorCollection;
use ICanBoogie\Operation;

class LocationOperation extends Operation
{
	public function validate(ErrorCollection $errors)
	{
		return true;
	}

	public function process()
	{
		$this->response->location = '/path/to/redirection/';

		return true;
	}
}
