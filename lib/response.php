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

use ICanBoogie\Errors;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\ToArray;
use ICanBoogie\ToArrayRecursive;

/**
 * @property string $message The response message.
 * @property-read \ICanBoogie\Errors $errors
 */
class Response extends \ICanBoogie\HTTP\Response implements \ArrayAccess
{
	/**
	 * Result of the response.
	 *
	 * @var mixed
	 */
	public $rc;

	/**
	 * Message associated with the response.
	 *
	 * @var string|array
	 */
	private $message;

	/**
	 * Sets the response message.
	 *
	 * @param string $message
	 *
	 * @throws \InvalidArgumentException if the message is an array or an object that do not implement `__toString()`.
	 */
	protected function set_message($message)
	{
		if (is_array($message) || (is_object($message) && !method_exists($message, '__toString')))
		{
			throw new \InvalidArgumentException(\ICanBoogie\format
			(
				'Invalid message type "{0}", shoud be a string or an object implementing "__toString()". Given: {1}', array
				(
					gettype($message), $message
				)
			));
		}

		$this->message = $message;
	}

	/**
	 * Returns the response message.
	 *
	 * @return string
	 */
	protected function get_message()
	{
		return $this->message;
	}

	/**
	 * Errors occuring during the response.
	 *
	 * @var Errors
	 */
	public $errors;

	protected $metas = [];

	/**
	 * Initializes the {@link $errors} property.
	 */
	public function __construct($body=null, $status=200, array $headers=[])
	{
		parent::__construct($body, $status, $headers);

		$this->errors = new Errors();
	}

	/**
	 * If `$body` is null the function does nothing.
	 *
	 * If {@link $rc} is a closure `$body` is set to {@link $rc}.
	 *
	 * Otherwise a JSON string is created with the message, errors and {@link $metas} of the
	 * response. If the response is successful the {@link $rc} property is also present. This JSON
	 * string is set in `$body`. The `Content-Type` header field is set to
	 * "application/json" and the `Content-Length` header field is set to the lenght of the JSON
	 * string.
	 *
	 * @see finalize_rc()
	 */
	protected function finalize(Headers &$headers, &$body)
	{
		parent::finalize($headers, $body);

		if ($body !== null)
		{
			return;
		}

		$rc = $this->rc;

		# streaming

		if ($rc instanceof \Closure)
		{
			$body = $rc;

			return;
		}

		# json response

		$data = array_filter([

			'message' => $this->finalize_message($this->message),
			'errors'  => $this->finalize_errors($this->errors)

		]) + array_map(function($v) { return (string) $v; }, $this->metas);

		if ($this->is_successful)
		{
			$data = [ 'rc' => $this->finalize_rc($rc) ] + $data;
		}

		$body = json_encode($data);

		$headers['Content-Type'] = 'application/json';
		$headers['Content-Length'] = strlen($body);
	}

	protected function finalize_rc($rc)
	{
		if (is_object($rc))
		{
			if (method_exists($rc, '__toString'))
			{
				return (string) $rc;
			}

			if ($rc instanceof ToArrayRecursive)
			{
				return $rc->to_array_recursive();
			}

			if ($rc instanceof ToArray)
			{
				return $rc->to_array();
			}
		}

		return $rc;
	}

	protected function finalize_message($message)
	{
		return (string) $message;
	}

	protected function finalize_errors($errors)
	{
		$simplified = [];

		foreach ($errors as $identifier => $message)
		{
			if (!$identifier)
			{
				$identifier = '_base';
			}

			if (isset($simplified[$identifier]))
			{
				$simplified[$identifier] .= '; ' . $message;
			}
			else
			{
				$simplified[$identifier] = is_bool($message) ? $message : (string) $message;
			}
		}

		return $simplified;
	}

	/**
	 * Checks if a meta exists.
	 */
	public function offsetExists($offset)
	{
		return isset($this->metas[$offset]);
	}

	/**
	 * Returns a meta or null if it is not defined.
	 */
	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->metas[$offset] : null;
	}

	/**
	 * Sets a meta.
	 */
	public function offsetSet($offset, $value)
	{
		$this->metas[$offset] = $value;
	}

	/**
	 * Unsets a meta.
	 */
	public function offsetUnset($offset)
	{
		unset($this->metas[$offset]);
	}
}