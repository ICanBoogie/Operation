<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Operation;

use ICanBoogie\Operation;
use ICanBoogie\PropertyNotDefined;

/**
 * Exception raised when an operation fails.
 *
 * @property-read Operation $operation The operation that failed.
 */
class Failure extends \ICanBoogie\HTTP\HTTPError
{
	private $operation;

	public function __construct(Operation $operation, \Exception $previous=null)
	{
		$this->operation = $operation;

		$message = $this->format_message($operation);
		$code = $operation->response->status;

		parent::__construct($message, $code, $previous);
	}

	protected function format_message(Operation $operation)
	{
		$message = $operation->response->message ?: "The operation failed.";

		if ($operation->response->errors->count())
		{
			$message .= "\n\nThe following errors where raised:";

			foreach ($operation->response->errors as $id => $error)
			{
				$message .= "\n– $error";
			}
		}

		return $message;
	}

	public function __get($property)
	{
		if ($property == 'operation')
		{
			return $this->operation;
		}

		throw new PropertyNotDefined(array($property, $this));
	}
}