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

class FailureEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_instance()
	{
		$operation = $this
			->getMockBuilder(Operation::class)
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$request = $this
			->getMockBuilder(Request::class)
			->disableOriginalConstructor()
			->getMock();

		/* @var $operation Operation */
		/* @var $request Request */

		$event = new FailureEvent($operation, FailureEvent::TYPE_CONTROL, $request);
		$this->assertTrue($event->is_control);
		$this->assertFalse($event->is_validate);

		$event = new FailureEvent($operation, FailureEvent::TYPE_VALIDATE, $request);
		$this->assertFalse($event->is_control);
		$this->assertTrue($event->is_validate);
	}
}
