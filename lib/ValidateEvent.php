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
 * Event class for the `ICanBoogie\Operation::validate` event.
 */
class ValidateEvent extends \ICanBoogie\Event
{
	use ValidateEventTrait;

	/**
	 * The event is constructed with the type `validate`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'validate', $payload);
	}
}
