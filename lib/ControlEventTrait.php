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

trait ControlEventTrait
{
	/**
	 * Reference to the success result of the control.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Reference to operation controls.
	 *
	 * @var array
	 */
	public $controls;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;
}
