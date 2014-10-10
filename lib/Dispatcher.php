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
use ICanBoogie\Operation;
use ICanBoogie\Operation\Dispatcher\BeforeDispatchEvent;
use ICanBoogie\Operation\Dispatcher\DispatchEvent;

/**
 * Dispatches operation requests.
 */
class Dispatcher implements \ICanBoogie\HTTP\DispatcherInterface
{
	/**
	 * Tries to create an {@link Operation} instance from the specified request. The operation
	 * is then executed and its response returned.
	 *
	 * If an operation could be created from the request, the `operation` property of the request's
	 * context is set to that operation.
	 *
	 * For forwarded operation, successful responses are not returned unless the request is an XHR
	 * or the response has a location.
	 */
	public function __invoke(Request $request)
	{
		$request->context->operation = $operation = Operation::from($request);

		if (!$operation)
		{
			return;
		}

		new BeforeDispatchEvent($this, $operation, $request, $response);

		if (!$response)
		{
			$response = $operation($request);
		}

		new DispatchEvent($this, $operation, $request, $response);

		if ($operation->is_forwarded && !$request->is_xhr && !$response->location)
		{
			return;
		}

		return $response;
	}

	/**
	 * Try to rescue the exception.
	 *
	 * The rescue process consists in the following steps:
	 *
	 * 1. The operation associated with the exception cannot be retrieved the exception is
	 * re-thrown.
	 * 2. Otherwise, the `ICanBoogie\Operation::rescue` event of class {@link \ICanBoogie\Operation\RescueEvent}
	 * is fired. Event hooks attached to this event may replace the exception or provide a
	 * response. If a response is provided it is returned.
	 * 3. Otherwise, if the exception is not an instance of {@link Failure} the exception is
	 * re-thrown.
	 * 4. Otherwise, if the request is an XHR the response of the operation is returned.
	 * 5. Otherwise, if the operation was forwarded the exception message is logged as an error
	 * and the method returns.
	 * 6. Otherwise, the exception is re-thrown.
	 *
	 * In summary, a failed operation is rescued if a response is provided during the
	 * `ICanBoogie\Operation::rescue` event, or later if the request is an XHR. Although the
	 * rescue of an operation might be successful, the returned response can be an error response.
	 *
	 * @param \Exception $exception The exception to rescue.
	 * @param Request $request The request.
	 *
	 * @return Response|null A response or `null` if the operation was forwarded.
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		$failure = null;

		if ($exception instanceof Failure)
		{
			$failure = $exception;

			if ($failure->previous)
			{
				$exception = $failure->previous;
			}

			$operation = $failure->operation;
		}
		else if (!empty($request->context->operation))
		{
			$operation = $request->context->operation;
		}
		else
		{
			#
			# The exception is re-thrown because it is not of type Failure, and we have no way to
			# retrieve the operation to rescue it.
			#

			throw $exception;
		}

		#
		# We try to rescue the exception, the original exception in case of a Failure exception. If
		# the exception is rescued we return the response, otherwise if the exception is not a
		# Failure we re-throw the exception.
		#

		new RescueEvent($operation, $exception, $request, $response);

		if ($response)
		{
			return $response;
		}

		if (!$failure)
		{
			throw $exception;
		}

		#
		# The exception is a Failure, which means that an exception occurred during
		# control/validate/process or a Failure was thrown because the response has an error.
		#
		# - If the request is an XHR we return the response of the operation.
		#
		# - If the operation is forwarded, the response of the actual URL needs to be displayed,
		# thus we cannot return a response nor re-throw the exception. In that case the message
		# of the exception is simply logged as an error and the method returns.
		#
		# - Otherwise, the exception is re-thrown.
		#

		if ($request->is_xhr)
		{
			return $operation->response;
		}

		if ($operation->is_forwarded)
		{
			\ICanBoogie\log_error($exception->getMessage());

			return;
		}

		throw $exception;
	}
}