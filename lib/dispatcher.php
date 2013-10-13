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

/**
 * Dispatches operation requests.
 */
class Dispatcher implements \ICanBoogie\HTTP\IDispatcher
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

		$response = $operation($request);

		if ($operation->is_forwarded && !$request->is_xhr && !$response->location)
		{
			return;
		}

		return $response;
	}

	/**
	 * Fires {@link \ICanBoogie\Operation\RescueEvent} and returns the response provided
	 * by third parties. If no response was provided, the exception (or the exception provided by
	 * third parties) is rethrown.
	 *
	 * @param \Exception $exception The exception to rescue.
	 * @param Request $request The request.
	 *
	 * @return \ICanBoogie\HTTP\Response If the exception is rescued, the response is returned,
	 * otherwise the exception is rethrown.
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		if (!empty($request->context->operation))
		{
			new Operation\RescueEvent($exception, $request, $request->context->operation, $response);

			if ($response)
			{
				return $response;
			}
		}

		if ($exception instanceof Failure)
		{
			$operation = $exception->operation;

			if ($operation->request->is_xhr)
			{
				return $operation->response;
			}

			#
			# if the operation was forwarded we simply return so that the response for the actual
			# URL is returned.
			#

			else if ($operation->is_forwarded)
			{
				return;
			}
		}

		throw $exception;
	}
}

/*
 * Events
 */

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

/**
 * Event class for the `ICanBoogie\Operation::rescue` event.
 *
 * The class extends {@link \ICanBoogie\Exception\RescueEvent} to provide the operation object
 * which processing raised an exception.
 */
class RescueEvent extends \ICanBoogie\Exception\RescueEvent
{
	/**
	 * Operation to rescue.
	 *
	 * @var \ICanBoogie\Operation
	 */
	public $operation;

	/**
	 * Initializes the {@link $operation} property.
	 *
	 * @param \Exception $target
	 * @param \ICanBoogie\HTTP\Request $request
	 * @param \ICanBoogie\Operation $operation
	 * @param \ICanBoogie\HTTP\Response|null $response
	 */
	public function __construct(\Exception &$target, Request $request, Operation $operation, &$response)
	{
		$this->operation = $operation;

		parent::__construct($target, $request, $response);
	}
}