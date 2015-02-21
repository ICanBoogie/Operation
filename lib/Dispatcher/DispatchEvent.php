<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Operation\Dispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;
use ICanBoogie\Operation\Dispatcher;

/**
 * Event class for the `ICanBoogie\Operation\Dispatcher::dispatch` event.
 *
 * Third parties may use this event to alter the response before it is returned by the dispatcher.
 */
class DispatchEvent extends Event
{
	/**
	 * The operation.
	 *
	 * @var Operation
	 */
	public $operation;

	/**
	 * The request.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * Reference to the response.
	 *
	 * @var \ICanBoogie\HTTP\Response|null
	 */
	public $response;

	/**
	 * The event is constructed with the type `dispatch`.
	 *
	 * @param Dispatcher $target
	 * @param Operation $operation
	 * @param Request $request
	 * @param \ICanBoogie\HTTP\Response $response
	 */
	public function __construct(Dispatcher $target, Operation $operation, Request $request, &$response)
	{
		$this->operation = $operation;
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, 'dispatch');
	}
}
