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
 * Event class for the `ICanBoogie\Operation::control` event.
 *
 * Third parties may use this event to alter the outcome of the control.
 */
class ControlEvent extends Event
{
	use ControlEventTrait;

	const TYPE = 'control';

	/**
	 * The event is constructed with the type `control`.
	 *
	 * @param Operation $target
	 * @param array $payload
	 */
	public function __construct(Operation $target, array $payload)
	{
		parent::__construct($target, self::TYPE, $payload);
	}
}
