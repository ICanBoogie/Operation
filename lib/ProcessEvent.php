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

/**
 * Event class for the `ICanBoogie\Operation::process` event.
 */
class ProcessEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the response result property.
	 *
	 * @var mixed
	 */
	public $rc;

	/**
	 * The response object of the operation.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `process`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'process', $payload);
	}
}
