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
use ICanBoogie\Operation;

/**
 * Event class for the `ICanBoogie\Operation::process:before` event.
 *
 * Third parties may use this event to alter the request, response or errors.
 */
class BeforeProcessEvent extends Event
{
	const TYPE = 'process:before';

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The response of the operation.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The errors collector.
	 *
	 * @var \ICanBoogie\ErrorCollection
	 */
	public $errors;

	/**
	 * The event is constructed with the type `process:before`.
	 *
	 * @param Operation $target
	 * @param array $payload
	 */
	public function __construct(Operation $target, array $payload)
	{
		parent::__construct($target, self::TYPE, $payload);
	}
}
