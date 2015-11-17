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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\Operation;

/**
 * Exception thrown when the form associated with the operation cannot be found.
 *
 * @property-read Operation $operation
 */
class FormNotFound extends \RuntimeException implements Exception
{
	use AccessorTrait;

	/**
	 * @var Operation
	 */
	private $operation;

	/**
	 * @return Operation
	 */
	protected function get_operation()
	{
		return $this->operation;
	}

	/**
	 * FormNotFound constructor.
	 *
	 * @param Operation $operation
	 * @param string|null $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct(Operation $operation, $message = null, $code = 500, \Exception $previous = null)
	{
		$this->operation = $operation;

		$message = $message ?: \ICanBoogie\format("Unable to retrieve form for operation %operation.", [

			'%operation' => get_class($operation)

		]);

		parent::__construct($message, $code, $previous);
	}
}
