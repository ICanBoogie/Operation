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

use ICanBoogie\ErrorCollection;
use ICanBoogie\HTTP\Headers;
use ICanBoogie\HTTP\Status;
use ICanBoogie\ToArray;
use ICanBoogie\ToArrayRecursive;

/**
 * @property string $message The response message.
 * @property ErrorCollection $errors
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
	 * @var string|null
	 */
	private $message;

	protected function set_message(?string $message): void
	{
		$this->message = $message;
	}

	protected function get_message(): ?string
	{
		return $this->message;
	}

	/**
	 * Errors occurring during the response.
	 *
	 * @var ErrorCollection
	 */
	public $errors;

	/**
	 * Additional response properties.
	 *
	 * @var array
	 */
	protected $meta = [];

	/**
	 * Initializes the {@link $errors} property.
	 *
	 * @inheritdoc
	 */
	public function __construct($body = null, $status = Status::OK, array $headers = [])
	{
		parent::__construct($body, $status, $headers);

		$this->errors = new ErrorCollection;
	}

	/**
	 * If `$body` is null the function does nothing.
	 *
	 * If {@link $rc} is a closure `$body` is set to {@link $rc}.
	 *
	 * Otherwise a JSON string is created with the message, errors and {@link $meta} of the
	 * response. If the response is successful the {@link $rc} property is also present. This JSON
	 * string is set in `$body`. The `Content-Type` header field is set to
	 * "application/json" and the `Content-Length` header field is set to the length of the JSON
	 * string.
	 *
	 * @inheritdoc
	 */
	protected function finalize(Headers &$headers, &$body): void
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

		$this->finalize_as_json($this->finalize_as_array($rc), $headers, $body);
	}

	/**
	 * Finalizes the response as an array.
	 *
	 * The array contains the following keys:
	 *
	 * - `rc`: The result of the operation. This key absent if the response is not successful.
	 * - `message`: The message associated with the response, a success or error message.
	 * - `errors`: An array of errors, which might only be present if the response is not
	 * successful.
	 *
	 * @param mixed $rc
	 *
	 * @return array
	 */
	private function finalize_as_array($rc): array
	{
		$data = \array_filter([

			'message' => $this->finalize_message($this->message),
			'errors'  => $this->finalize_errors($this->errors)

		]) + \array_map(function($v) { return $this->finalize_value($v); }, $this->meta);

		if ($this->status->is_successful)
		{
			$data = [ 'rc' => $this->finalize_rc($rc) ] + $data;
		}

		return $data;
	}

	/**
	 * Finalizes the response as a JSON string.
	 *
	 * The following methods are invoked
	 *
	 * @param array $rc
	 * @param Headers $headers
	 * @param mixed $body
	 */
	private function finalize_as_json(array $rc, Headers &$headers, &$body): void
	{
		$body = \json_encode($rc);

		$headers['Content-Type'] = 'application/json';
	}

	/**
	 * Tries to transforms a value into a simple type such as a scalar or an array.
	 *
	 * The following transformations occur:
	 *
	 * - `$value` is an object and implements `__toString`: A string is returned.
	 * - `$value` is an object and implements {@link ToArrayRecursive}: An array is returned.
	 * - `$value` is an object and implements {@link ToArray}: An array is returned.
	 *
	 * Otherwise the value is returned as is.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	private function finalize_value($value)
	{
		return is_object($value) ? $this->finalize_value_object($value) : $value;
	}

	/**
	 * Finalizes value object.
	 *
	 * - If the value implements `__toString` the value is cast as a string.
	 * - If the value is an instance of {@link ToArrayRecursive} the value is converted into an array.
	 * - If the value is an instance of {@link ToArray} the value is converted into an array.
	 * - Otherwise the value is returned as is.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	private function finalize_value_object($value)
	{
		if (method_exists($value, '__toString'))
		{
			return (string) $value;
		}

		if ($value instanceof ToArrayRecursive)
		{
			return $value->to_array_recursive();
		}

		if ($value instanceof ToArray)
		{
			return $value->to_array();
		}

		return $value;
	}

	/**
	 * Finalizes a value of the {@link $rc} property using {@link finalize_value()}.
	 *
	 * @param mixed $rc
	 *
	 * @return mixed
	 */
	private function finalize_rc($rc)
	{
		return $this->finalize_value($rc);
	}

	/**
	 * Finalizes a message.
	 *
	 * @param mixed $message
	 *
	 * @return string
	 */
	private function finalize_message($message): string
	{
		return (string) $message;
	}

	/**
	 * Finalizes errors into a nice array.
	 *
	 * @param array|mixed $errors
	 *
	 * @return array
	 */
	private function finalize_errors($errors): array
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
				$simplified[$identifier] = \is_bool($message) ? $message : (string) $message;
			}
		}

		return $simplified;
	}

	/**
	 * Checks if a meta exists.
	 *
	 * @inheritdoc
	 */
	public function offsetExists($offset)
	{
		return isset($this->meta[$offset]);
	}

	/**
	 * Returns a meta or null if it is not defined.
	 *
	 * @inheritdoc
	 */
	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->meta[$offset] : null;
	}

	/**
	 * Sets a meta.
	 *
	 * @inheritdoc
	 */
	public function offsetSet($offset, $value)
	{
		$this->meta[$offset] = $value;
	}

	/**
	 * Unset a meta.
	 *
	 * @inheritdoc
	 */
	public function offsetUnset($offset)
	{
		unset($this->meta[$offset]);
	}
}
