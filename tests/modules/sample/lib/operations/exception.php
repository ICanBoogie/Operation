<?php

namespace ICanBoogie\Operation\Modules\Sample;

class ExceptionOperation extends \ICanBoogie\Operation
{
	protected function validate(\ICanBoogie\Errors $errors)
	{
		throw new SampleException('Validate failed.');
	}

	protected function process()
	{
		return true;
	}
}

class SampleException extends \Exception
{

}