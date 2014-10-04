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

trait ValidateEventTrait
{
	/**
	 * Reference the success of the validation.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Reference to the validation errors.
	 *
	 * @var \ICanBoogie\Errors
	 */
	public $errors;

	/**
	 * Request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;
}