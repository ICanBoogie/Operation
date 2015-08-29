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

use ICanBoogie\Operation\Modules\Sample\Operation\SuccessOperation;
use ICanBoogie\Operation\Modules\Sample\Operation\ErrorOperation;
use ICanBoogie\Operation\Modules\Sample\Operation\FailureOperation;
use ICanBoogie\Operation\Modules\Sample\Operation\ExceptionOperation;
use ICanBoogie\Operation\OperationTest\LocationOperation;
use ICanBoogie\HTTP\Response;

class OperationTest extends \PHPUnit_Framework_TestCase
{
	public function test_operation_invoke_successful()
	{
		/* @var $app \ICanBoogie\Core|\ICanBoogie\Binding\Event\CoreBindings */

		$app = \ICanBoogie\app();

		$before_control_event_called = false;
		$before_control_eh = $app->events->attach(function(Operation\BeforeControlEvent $event, SuccessOperation $target) use(&$before_control_event_called) {

			$before_control_event_called = true;

		});

		$control_event_called = false;
		$control_eh = $app->events->attach(function(Operation\ControlEvent $event, SuccessOperation $target) use(&$control_event_called) {

			$control_event_called = true;

		});

		$before_validate_event_called = false;
		$before_validate_eh = $app->events->attach(function(Operation\BeforeValidateEvent $event, SuccessOperation $target) use(&$before_validate_event_called) {

			$before_validate_event_called = true;

		});

		$validate_event_called = false;
		$validate_eh = $app->events->attach(function(Operation\ValidateEvent $event, SuccessOperation $target) use(&$validate_event_called) {

			$validate_event_called = true;

		});

		$before_process_event_called = false;
		$before_process_eh = $app->events->attach(function(Operation\BeforeProcessEvent $event, SuccessOperation $target) use(&$before_process_event_called) {

			$before_process_event_called = true;

		});

		$process_event_called = false;
		$process_eh = $app->events->attach(function(Operation\ProcessEvent $event, SuccessOperation $target) use(&$process_event_called) {

			$process_event_called = true;

		});

		$operation = new SuccessOperation;
		$response = $operation(Request::from());

		$before_control_eh->detach();
		$control_eh->detach();
		$before_validate_eh->detach();
		$validate_eh->detach();
		$before_process_eh->detach();
		$process_eh->detach();

		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertTrue($before_control_event_called);
		$this->assertTrue($control_event_called);
		$this->assertTrue($before_validate_event_called);
		$this->assertTrue($validate_event_called);
		$this->assertTrue($before_process_event_called);
		$this->assertTrue($process_event_called);
		$this->assertTrue($response->status->is_successful);
		$this->assertNotNull($response->rc);
		$this->assertEquals(0, $response->errors->count());
	}

	/**
	 * When errors are collected during `validate()` the `failure` event must be fired. The
	 * response must have a 4XX status code.
	 */
	public function test_operation_invoke_errored()
	{
		$app = \ICanBoogie\app();

		$operation = new ErrorOperation;
		$failure_event_called = false;
		$failure_event_type = null;

		$eh = $app->events->attach(function(\ICanBoogie\Operation\FailureEvent $event, ErrorOperation $target) use(&$failure_event_called, &$failure_event_type) {

			$failure_event_called = true;
			$failure_event_type = $event->type;

		});

		try
		{
			$response = $operation(Request::from());
			$this->fail('The Failure exception should have been raised');
		}
		catch (\ICanBoogie\Operation\Failure $e)
		{
			$this->assertTrue($operation->response->status->is_client_error);
			$this->assertNotEquals(0, $operation->response->errors->count());
			$this->assertTrue($failure_event_called);
		}

		$eh->detach();
	}

	/**
	 * @expectedException \ICanBoogie\Operation\Failure
	 */
	public function test_operation_invoke_failed()
	{
		$operation = new FailureOperation;
		$operation(Request::from());
	}

	public function test_operation_invoke_exception()
	{
		$operation = new ExceptionOperation;

		try
		{
			$response = $operation(Request::from());

			$this->fail("Expected Failure");
		}
		catch (Failure $e)
		{
			$previous = $e->previous;
			$response = $e->operation->response;

			$this->assertInstanceOf(Operation\Modules\Sample\SampleException::class, $previous);
			$this->assertEquals($previous->getMessage(), $response->message);
		}
		catch (\Exception $e)
		{
			$this->fail("Expected Failure");
		}
	}

	/**
	 * @expectedException \ICanBoogie\Operation\Modules\Sample\SampleException
	 */
	public function test_operation_invoke_exception_using_dispatch()
	{
		$request = Request::from('/api/exception');
		$request();
	}

	public function test_operation_invoke_exception_using_dispatch_and_xhr()
	{
		$request = Request::from([

			'uri' => '/api/exception',
			'is_xhr' => true

		]);

		$response = $request();
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals("My Exception Message.", $response->message);
		$this->assertFalse($response->status->is_successful);
		$this->assertEquals(500, $response->status->code);
	}

	public function test_operation_rescue()
	{
		$app = \ICanBoogie\app();

		$rescue_response = new Response("Rescued!", 200);

		$eh = $app->events->attach(function(RescueEvent $event, ExceptionOperation $target) use($rescue_response) {

			$event->response = $rescue_response;

		});

		$request = Request::from('/api/exception');
		$response = $request();

		$this->assertSame($rescue_response, $response);

		$eh->detach();
	}

	public function test_operation_replace_exception()
	{
		$app = \ICanBoogie\app();

		$exception = new \Exception("My exception");

		$eh = $app->events->attach(function(RescueEvent $event, ExceptionOperation $target) use($exception) {

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

		$eh->detach();
	}

	/*
	 * Whatever the outcome of a forwarded operation, the dispatcher must not return a response,
	 * unless the request is an XHR.
	 */

	public function test_forwarded_success()
	{
		$request = Request::from([

			'path' => '/',
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success'

			]
		]);

		$dispatcher = new OperationDispatcher;
		$response = $dispatcher($request);
		$this->assertNull($response);
	}

	public function test_forwarded_success_with_location()
	{
		$request = Request::from([

			'path' => '/',
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success_with_location'

			]
		]);

		$dispatcher = new OperationDispatcher;
		$response = $dispatcher($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertNotNull($response->location);
	}

	public function test_forwarded_error()
	{
		$request = Request::from([

			'path' => '/',
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'error'

			]
		]);

		$dispatcher = new OperationDispatcher;

		try
		{
			$response = $dispatcher($request);
			$this->fail('The Failure exception should have been raised.');
		}
		catch (\ICanBoogie\Operation\Failure $exception)
		{
			$response = $dispatcher->rescue($exception, $request);
			$this->assertNull($response);
		}
	}

	public function test_forwarded_failure()
	{
		$request = Request::from([

			'path' => '/',
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'failure'

			]
		]);

		$dispatcher = new OperationDispatcher;

		try
		{
			$response = $dispatcher($request);
		}
		catch (\ICanBoogie\Operation\Failure $exception)
		{
			$response = $dispatcher->rescue($exception, $request);
			$this->assertNull($response);

			return;
		}

		$this->fail('The Failure exception should have been raised.');
	}

	/*
	 * The response to a forwarded operation must be return if the request is an XHR.
	 */

	public function test_forwarded_success_with_xhr()
	{
		$request = Request::from([

			'path' => '/',
			'is_xhr' => true,
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success'

			]
		]);

		$dispatcher = new OperationDispatcher;
		$response = $dispatcher($request);

		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals(200, $response->status->code);
		$this->assertTrue($response->status->is_successful);
	}

	public function test_forwarded_success_with_xhr_and_location()
	{
		$request = Request::from([

			'path' => '/',
			'is_xhr' => true,
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success_with_location'

			]
		]);

		$dispatcher = new OperationDispatcher;
		$response = $dispatcher($request);

		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals(200, $response->status->code);
		$this->assertTrue($response->status->is_successful);
		$this->assertNull($response->location);
		$this->assertNotNull($response['redirect_to']);
	}

	public function test_forwarded_error_with_xhr()
	{
		$request = Request::from([

			'path' => '/',
			'is_xhr' => true,
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'error'

			]
		]);

		$dispatcher = new OperationDispatcher;

		try
		{
			$response = $dispatcher($request);
			$this->fail('The Failure exception should have been raised.');
		}
		catch (\ICanBoogie\Operation\Failure $exception)
		{
			$response = $dispatcher->rescue($exception, $request);
			$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		}
	}

	public function test_forwarded_failure_with_xhr()
	{
		$request = Request::from([

			'path' => '/',
			'is_xhr' => true,
			'request_params' => [

				Operation::DESTINATION => 'sample',
				Operation::NAME => 'failure'

			]
		]);

		$dispatcher = new OperationDispatcher;

		try
		{
			$response = $dispatcher($request);
			$this->fail('The Failure exception should have been raised.');
		}
		catch (\ICanBoogie\Operation\Failure $exception)
		{
			$response = $dispatcher->rescue($exception, $request);
			$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		}
	}

	public function test_response_location()
	{
		$request = Request::from('/api/test/location');
		$operation = new LocationOperation;
		$response = $operation($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals('/path/to/redirection/', $response->location);
		$this->assertFalse($response->offsetExists('redirect_to'));
	}

	public function test_response_location_with_xhr()
	{
		$request = Request::from([

			'path' => '/api/test/location',
			'is_xhr' => true

		]);

		$operation = new LocationOperation;
		$response = $operation($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertNull($response->location);
		$this->assertTrue($response->offsetExists('redirect_to'));
		$this->assertEquals('/path/to/redirection/', $response['redirect_to']);
	}

	public function test_from_route()
	{
		$app = \ICanBoogie\app();

		$app->routes['api:nodes/online'] = [

			'pattern' => '/api/:constructor/<nid:\d+>/is_online',
			'controller' => 'ICanBoogie\Operation\Modules\Sample\OnlineOperation',
			'via' => 'PUT',
			'param_translation_list' => [

				'constructor' => Operation::DESTINATION,
				'nid' => Operation::KEY

			]
		];

		$request = Request::from([

			'path' => '/api/sample/123/is_online',
			'is_put' => true

		]);

		$operation = Operation::from($request);

		$this->assertArrayHasKey(Operation::DESTINATION, $request->path_params);
		$this->assertEquals('sample', $request->path_params[Operation::DESTINATION]);
		$this->assertArrayHasKey(Operation::KEY, $request->path_params);
		$this->assertEquals(123, $request->path_params[Operation::KEY]);

		$this->assertInstanceOf('ICanBoogie\Operation\Modules\Sample\OnlineOperation', $operation);
		$this->assertInstanceOf('ICanBoogie\Operation\Modules\Sample\Module', $operation->module);
		$this->assertEquals(123, $operation->key);
	}
}

namespace ICanBoogie\Operation\OperationTest;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class LocationOperation extends Operation
{
	public function validate(Errors $errors)
	{
		return true;
	}

	public function process()
	{
		$this->response->location = '/path/to/redirection/';

		return true;
	}
}
