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

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

/**
 * Event class for the `ICanBoogie\Operation::rescue` event.
 */
class RescueEvent extends Event
{
	const TYPE = 'rescue';

	/**
	 * Reference to the exception that made the operation fail.
	 *
	 * @var \Throwable
	 */
	public $exception;

	/**
	 * The request.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * Reference to the rescue response.
	 *
	 * @var Response
	 */
	public $response;

	/**
	 * The event is constructed with the type `rescue`.
	 *
	 * @param Operation $target
	 * @param \Throwable $exception
	 * @param Request $request
	 * @param Response|null $response
	 */
	public function __construct(Operation $target, \Throwable &$exception, Request $request, &$response)
	{
		$this->exception = &$exception;
		$this->request = $request;
		$this->response = &$response;

		parent::__construct($target, self::TYPE);
	}
}
