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
 * @property-read \Exception $previous The previous exception.
 */
class Failure extends \ICanBoogie\HTTP\HTTPError
{
	use \ICanBoogie\GetterTrait;

	private $operation;

	protected function get_operation()
	{
		return $this->operation;
	}

	protected function get_previous()
	{
		return $this->getPrevious();
	}

	/**
	 * Initialize the {@link $operation} property.
	 *
	 * @param Operation $operation
	 * @param \Exception $previous
	 */
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
		$errors = $operation->response->errors;

		if ($errors->count())
		{
			$message .= "\n\nThe following errors where raised:";

			foreach ($errors as $id => $error)
			{
				if ($error === true)
				{
					continue;
				}

				$message .= "\n– $error";
			}
		}

		return $message;
	}
}