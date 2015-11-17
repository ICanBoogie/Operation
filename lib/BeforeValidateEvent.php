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
 * Event class for the `ICanBoogie\Operation::validate:before` event.
 */
class BeforeValidateEvent extends Event
{
	use ValidateEventTrait;

	const TYPE = 'validate:before';

	/**
	 * The event is constructed with the type `validate:before`.
	 *
	 * @param Operation $target
	 * @param array $payload
	 */
	public function __construct(Operation $target, array $payload)
	{
		parent::__construct($target, self::TYPE, $payload);
	}
}
