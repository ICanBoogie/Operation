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
 * Exception thrown when the form associated with an operation has expired.
 *
 * The exception is considered recoverable, if the request is not XHR.
 */
class FormHasExpired extends \Exception implements Exception
{
	public function __construct($message="The form associated with the request has expired.", $code=500, \Exception $previous=null)
	{
		parent::__construct($message, $code, $previous);
	}
}
