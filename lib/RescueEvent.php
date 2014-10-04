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

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

/**
 * Event class for the `ICanBoogie\Operation::rescue` event.
 *
 * The class extends {@link \ICanBoogie\Exception\RescueEvent} to provide the operation object
 * which processing raised an exception.
 */
class RescueEvent extends \ICanBoogie\Exception\RescueEvent
{
	/**
	 * Operation to rescue.
	 *
	 * @var \ICanBoogie\Operation
	 */
	public $operation;

	/**
	 * Initializes the {@link $operation} property.
	 *
	 * @param \Exception $target
	 * @param \ICanBoogie\HTTP\Request $request
	 * @param \ICanBoogie\Operation $operation
	 * @param \ICanBoogie\HTTP\Response|null $response
	 */
	public function __construct(\Exception &$target, Request $request, Operation $operation, &$response)
	{
		$this->operation = $operation;

		parent::__construct($target, $request, $response);
	}
}