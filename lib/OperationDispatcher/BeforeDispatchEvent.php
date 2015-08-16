<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Operation\OperationDispatcher;

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;
use ICanBoogie\Operation;
use ICanBoogie\Operation\OperationDispatcher;

/**
 * Event class for the `ICanBoogie\Operation\OperationDispatcher::dispatch:before` event.
 *
 * Third parties may use this event to provide a response to the request before the route is
 * mapped. The event is usually used by third parties to redirect requests or provide cached
 * responses.
 */
class BeforeDispatchEvent extends Event
{
	/**
	 * The route.
	 *
	 * @var Operation
	 */
	public $operation;

	/**
	 * The HTTP request.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * Reference to the HTTP response.
	 *
	 * @var Response
	 */
	public $response;

	/**
	 * The event is constructed with the type `dispatch:before`.
	 *
	 * @param OperationDispatcher $target
	 * @param Operation $operation
	 * @param Request $request
	 * @param Response|null $response
	 */
	public function __construct(OperationDispatcher $target, Operation $operation, Request $request, &$response)
	{
		if ($response !== null && !($response instanceof Response))
		{
			throw new \InvalidArgumentException('$response must be an instance of ICanBoogie\HTTP\Response. Given: ' . (is_object($response) ? get_class($response) : gettype($response)) . '.');
		}

		$this->operation = $operation;
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, 'dispatch:before');
	}
}
