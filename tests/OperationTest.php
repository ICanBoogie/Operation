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
use ICanBoogie\Routes;

use ICanBoogie\Operation\Modules\Sample\SuccessOperation;
use ICanBoogie\Operation\Modules\Sample\ErrorOperation;
use ICanBoogie\Operation\Modules\Sample\FailureOperation;
use ICanBoogie\Operation\Modules\Sample\ExceptionOperation;
use ICanBoogie\Operation\OperationTest\LocationOperation;

class OperationTest extends \PHPUnit_Framework_TestCase
{
	public function test_operation_invoke_successful()
	{
		global $core;

		$before_control_event_called = false;
		$before_control_eh = $core->events->attach(function(Operation\BeforeControlEvent $event, SuccessOperation $target) use(&$before_control_event_called) {

			$before_control_event_called = true;

		});

		$control_event_called = false;
		$control_eh = $core->events->attach(function(Operation\ControlEvent $event, SuccessOperation $target) use(&$control_event_called) {

			$control_event_called = true;

		});

		$before_validate_event_called = false;
		$before_validate_eh = $core->events->attach(function(Operation\BeforeValidateEvent $event, SuccessOperation $target) use(&$before_validate_event_called) {

			$before_validate_event_called = true;

		});

		$validate_event_called = false;
		$validate_eh = $core->events->attach(function(Operation\ValidateEvent $event, SuccessOperation $target) use(&$validate_event_called) {

			$validate_event_called = true;

		});

		$before_process_event_called = false;
		$before_process_eh = $core->events->attach(function(Operation\BeforeProcessEvent $event, SuccessOperation $target) use(&$before_process_event_called) {

			$before_process_event_called = true;

		});

		$process_event_called = false;
		$process_eh = $core->events->attach(function(Operation\ProcessEvent $event, SuccessOperation $target) use(&$process_event_called) {

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
		$this->assertTrue($response->is_successful);
		$this->assertNotNull($response->rc);
		$this->assertEquals(0, $response->errors->count());
	}

	/**
	 * When errors are collected during `validate()` the `failure` event must be fired. The
	 * response must have a 4XX status code.
	 */
	public function test_operation_invoke_errored()
	{
		global $core;

		$operation = new ErrorOperation;
		$failure_event_called = false;
		$failure_event_type = null;

		$eh = $core->events->attach(function(\ICanBoogie\Operation\FailureEvent $event, ErrorOperation $target) use(&$failure_event_called, &$failure_event_type) {

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
			$this->assertTrue($operation->response->is_client_error);
			$this->assertNotEquals(0, $operation->response->errors->count());
			$this->assertTrue($failure_event_called);
		}

		$eh->detach();
	}

	/**
	 * @expectedException ICanBoogie\Operation\Failure
	 */
	public function test_operation_invoke_failed()
	{
		$operation = new FailureOperation;
		$response = $operation(Request::from());
	}

	/**
	 * @expectedException ICanBoogie\Operation\Modules\Sample\SampleException
	 */
	public function test_operation_invoke_exception()
	{
		$operation = new ExceptionOperation;
		$response = $operation(Request::from());
	}

	/*
	 * Whatever the outcome of a forwarded operation, the dispatcher must not return a response,
	 * unless the request is an XHR.
	 */

	public function test_forwarded_success()
	{
		$request = Request::from(array(

			'path' => '/',
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success'
			)
		));

		$dispatcher = new Dispatcher;
		$response = $dispatcher($request);
		$this->assertNull($response);
	}

	public function test_forwarded_error()
	{
		$request = Request::from(array(

			'path' => '/',
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'error'
			)
		));

		$dispatcher = new Dispatcher;

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
		$request = Request::from(array(

			'path' => '/',
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'failure'
			)
		));

		$dispatcher = new Dispatcher;

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
		$request = Request::from(array(

			'path' => '/',
			'is_xhr' => true,
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'success'
			)
		));

		$dispatcher = new Dispatcher;
		$response = $dispatcher($request);

		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals(200, $response->status);
		$this->assertTrue($response->is_successful);
	}

	public function test_forwarded_error_with_xhr()
	{
		$request = Request::from(array(

			'path' => '/',
			'is_xhr' => true,
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'error'
			)
		));

		$dispatcher = new Dispatcher;

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
		$request = Request::from(array(

			'path' => '/',
			'is_xhr' => true,
			'request_params' => array
			(
				Operation::DESTINATION => 'sample',
				Operation::NAME => 'failure'
			)
		));

		$dispatcher = new Dispatcher;

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
		$request = Request::from(array(

			'path' => '/api/test/location',
			'is_xhr' => true

		));

		$operation = new LocationOperation;
		$response = $operation($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertNull($response->location);
		$this->assertTrue($response->offsetExists('redirect_to'));
		$this->assertEquals('/path/to/redirection/', $response['redirect_to']);
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