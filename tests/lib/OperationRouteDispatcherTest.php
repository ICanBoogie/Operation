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
use ICanBoogie\Operation\Modules\Sample\Operation\ExceptionOperation;

class OperationRouteDispatcherTest
{
	public function test_operation_rescue()
	{
		$app = \ICanBoogie\app();

		$rescue_response = new Response("Rescued!", 200);

		$app->events->once(function(RescueEvent $event, ExceptionOperation $target) use($rescue_response) {

			$event->response = $rescue_response;

		});

		$request = Request::from('/api/exception');
		$response = $request();

		$this->assertSame($rescue_response, $response);
	}

	public function test_operation_replace_exception()
	{
		$app = \ICanBoogie\app();

		$exception = new \Exception("My exception");

		$app->events->once(function(RescueEvent $event, ExceptionOperation $target) use($exception) {

			$event->exception = $exception;

		});

		try
		{
			$request = Request::from('/api/exception');
			$request();

			$this->fail('An exception should have been raised.');
		}
		catch (\Exception $e)
		{
			$this->assertSame($exception, $e);
		}
	}
}
