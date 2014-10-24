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

/**
 * Event class for the `ICanBoogie\Operation::failure` event.
 *
 * @property-read bool $is_control `true` if failure occured during
 * {@link \ICanBoogie\Operation::control()}, `false` otherwise.
 * @property-read bool $is_validate `true` if failure occured during
 * {@link \ICanBoogie\Operation::validate()}, `false` otherwise.
 */
class FailureEvent extends \ICanBoogie\Event
{
	use \ICanBoogie\GetterTrait;

	/**
	 * The failure occured during {@link \ICanBoogie\Operation::control()}.
	 *
	 * @var string
	 */
	const TYPE_CONTROL = 'control';

	/**
	 * The failure occured during {@link \ICanBoogie\Operation::validate()}.
	 *
	 * @var string
	 */
	const TYPE_VALIDATE = 'validate';

	/**
	 * Type of failure, either {@link TYPE_CONTROL} or {@link TYPE_VALIDATION}.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `failure`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, $type, Request $request)
	{
		$this->type = $type;
		$this->request = $request;

		parent::__construct($target, 'failure');
	}

	protected function get_is_control()
	{
		return $this->type == self::TYPE_CONTROL;
	}

	protected function get_is_validate()
	{
		return $this->type == self::TYPE_VALIDATE;
	}
}
