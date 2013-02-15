<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Response;

/**
 * Dispatches operation requests.
 */
class OperationDispatcher implements \ICanBoogie\HTTP\IDispatcher
{
	/**
	 * Current operation.
	 *
	 * @var Operation
	 */
	private $operation;

	/**
	 * Tries to create an {@link Operation} instance from the specified request. The operation
	 * is then executed and its response returned.
	 *
	 * If an operation could be created from the request, the `operation` property of the request
	 * is set to that operation.
	 *
	 * If the operation returns an error response (client error or server error) and the resquest
	 * is not an XHR nor an API request, `null` is returned instead of the reponse to allow another
	 * controller to display an error message.
	 *
	 * If there is no response but the request is an API request, a 404 response is returned.
	 */
	public function __invoke(Request $request)
	{
		$operation = Operation::from($request);

		if (!$operation)
		{
			return;
		}

		$this->operation = $operation;

		$request->operation = $operation;

		$response = $operation($request);
		$is_api_operation = strpos(\ICanBoogie\Routing\decontextualize($request->path), '/api/') === 0;

		if ($response)
		{
			if (($response->is_client_error || $response->is_server_error) && !$request->is_xhr && !$is_api_operation)
			{
				return;
			}
		}
		else if ($is_api_operation)
		{
			return new Response(null, 404);
		}

		return $response;
	}

	/**
	 * Fires {@link \ICanBoogie\Operation\RescueEvent} and returns the response provided
	 * by third parties. If no response was provided, the exception (or the exception provided by
	 * third parties) is rethrown.
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		if ($this->operation)
		{
			new Operation\RescueEvent($exception, $request, $this->operation, $response);

			if ($response)
			{
				return $response;
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