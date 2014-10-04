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
 * Event class for the `ICanBoogie\Operation::control:before` event.
 *
 * Third parties may use this event to alter the controls to run or clear them altogether.
 */
class BeforeControlEvent extends \ICanBoogie\Event
{
	use ControlEventTrait;

	/**
	 * The event is constructed with the type `control:before`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'control:before', $payload);
	}
}