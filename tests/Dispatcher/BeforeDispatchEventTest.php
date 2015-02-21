<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Operation\Dispatcher;

class BeforeDispatchEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_response_must_be_instance_of_response()
	{
		$dispatcher = $this
			->getMockBuilder('ICanBoogie\Operation\Dispatcher')
			->disableOriginalConstructor()
			->getMock();

		$operation = $this
			->getMockBuilder('ICanBoogie\Operation')
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$request = $this
			->getMockBuilder('ICanBoogie\HTTP\Request')
			->disableOriginalConstructor()
			->getMock();

		/* @var $dispatcher \ICanBoogie\Operation\Dispatcher */
		/* @var $operation \ICanBoogie\Operation */
		/* @var $request \ICanBoogie\HTTP\Request */

		try
		{
			$response = uniqid();

			new BeforeDispatchEvent($dispatcher, $operation, $request, $response);

			$this->fail("Expected InvalidArgumentException");
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('InvalidArgumentException', $e);
			$this->assertStringStartsWith("\$response must be an instance of", $e->getMessage());
		}
	}
}
