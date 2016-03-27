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
use ICanBoogie\Routing\Route;
use ICanBoogie\Routing\RouteDispatcher;

/**
 * Extends the original routing dispatcher to provide extra care for operations.
 */
class OperationRouteDispatcher extends RouteDispatcher
{
	/**
	 * @inheritdoc
	 *
	 * Translates request path parameters using the route's `param_translation_list` property.
	 */
	protected function alter_params(Route $route, Request $request, array $captured)
	{
		parent::alter_params($route, $request, $captured);

		$path_params = &$request->path_params;

		if (!$path_params)
		{
			return;
		}

		if (empty($route->param_translation_list))
		{
			return;
		}

		$params = &$request->params;

		foreach ($route->param_translation_list as $from => $to)
		{
			$params[$to] = $path_params[$to] = $path_params[$from];
		}
	}

	/**
	 * @inheritdoc
	 *
	 * If the exception is a {@link Failure} instance and has a previous exception, it is replaced
	 * by that exception.
	 *
	 * @param \Exception $exception
	 * @param Request $request
	 *
	 * @return \ICanBoogie\HTTP\Response
	 */
	public function rescue(\Exception $exception, Request $request)
	{
		$failure = null;
		$operation = $this->resolve_operation($exception, $request, $failure);

		if (!$operation)
		{
			return parent::rescue($exception, $request);
		}

		return $this->rescue_operation($exception, $request, $operation, $failure);
	}

	/**
	 * Rescues an operation that raised an exception.
	 *
	 * A {@link RescueEvent} is fired to allow event hooks to replace the exception or provide
	 * a response.
	 *
	 * - If a response is provided it is returned.
	 * - If the original exception is not a failure it is re-thrown.
	 * - If the request is an XHR the response of the operation is returned as is.
	 * - Otherwise, the exception is re-thrown.
	 *
	 * @param \Exception $exception
	 * @param Request $request
	 * @param Operation $operation
	 * @param Failure|null $failure
	 *
	 * @return Response
	 *
	 * @throws \Exception
	 */
	protected function rescue_operation(\Exception $exception, Request $request, Operation $operation, Failure $failure = null)
	{
		$response = null;

		new RescueEvent($operation, $exception, $request, $response);

		if ($response)
		{
			return $response;
		}

		if (!$failure)
		{
			throw $exception;
		}

		if ($request->is_xhr)
		{
			return $operation->response;
		}

		throw $exception;
	}

	/**
	 * Resolves the operation from the exception or request's context.
	 *
	 * @param \Exception $exception
	 * @param Request $request
	 * @param Failure $failure
	 *
	 * @return Operation
	 */
	protected function resolve_operation(\Exception &$exception, Request $request, Failure &$failure = null)
	{
		if ($exception instanceof Failure)
		{
			$failure = $exception;

			if ($failure->previous)
			{
				$exception = $failure->previous;
			}

			return $failure->operation;
		}

		$context = $request->context;

		if (isset($context->controller) && $context->controller instanceof Operation)
		{
			return $context->controller;
		}

		return null;
	}
}
