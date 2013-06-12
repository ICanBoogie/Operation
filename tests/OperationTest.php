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

class OperationTest extends \PHPUnit_Framework_TestCase
{
	static public function setupBeforeClass()
	{
		$routes = Routes::get();
		$routes['api:test:location'] = array
		(
			'pattern' => '/api/test/location',
			'controller' => __CLASS__ . '\TestLocationOperation'
		);
	}

	public function testResponseLocation()
	{
		$request = Request::from('/api/test/location');
		$operation = Operation::from($request);
		$this->assertInstanceOf(__CLASS__ . '\TestLocationOperation', $operation);

		$response = $operation($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertEquals('/path/to/redirection/', $response->location);
		$this->assertFalse($response->offsetExists('redirect_to'));
	}

	public function testResponseLocationXHR()
	{
		$request = Request::from(array(

			'path' => '/api/test/location',
			'is_xhr' => true

		));

		$operation = Operation::from($request);
		$this->assertInstanceOf(__CLASS__ . '\TestLocationOperation', $operation);

		$response = $operation($request);
		$this->assertInstanceOf('ICanBoogie\Operation\Response', $response);
		$this->assertNull($response->location);
		$this->assertTrue($response->offsetExists('redirect_to'));
		$this->assertEquals('/path/to/redirection/', $response['redirect_to']);
	}
}

namespace ICanBoogie\OperationTest;

use ICanBoogie\Errors;
use ICanBoogie\Operation;

class TestLocationOperation extends Operation
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