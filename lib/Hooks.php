<?php

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\RequestDispatcher;
use ICanBoogie\Routing\RouteDispatcher;

class Hooks
{
	static public function on_request_dispatcher_alter(RequestDispatcher\AlterEvent $event, RequestDispatcher $target)
	{
		/* @var $routing RouteDispatcher */

		$routing = $target['routing'];
		$target['routing'] = new OperationRouteDispatcher($routing->routes);
	}
}
