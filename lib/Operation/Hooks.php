<?php

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\Routing\RouteDispatcher;

class Hooks
{
	/**
	 * Replaces `routing` dispatcher with an {@link OperationRouteDispatcher} instance.
	 *
	 * @param RequestDispatcher\AlterEvent $event
	 * @param RequestDispatcher $target
	 */
	static public function on_request_dispatcher_alter(RequestDispatcher\AlterEvent $event, RequestDispatcher $target)
	{
		/* @var $routing RouteDispatcher */

		$routing = $target['routing'];
		$target['routing'] = new OperationRouteDispatcher($routing->routes);
	}
}
