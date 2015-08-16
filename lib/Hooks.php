<?php

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\RequestDispatcher;

class Hooks
{
	/**
	 * @param RequestDispatcher\AlterEvent $event
	 * @param RequestDispatcher $target
	 */
	static public function on_alter_request_dispatcher(RequestDispatcher\AlterEvent $event, RequestDispatcher $target)
	{
		$event->insert_before('operation', new OperationDispatcher, 'routing');
	}
}
