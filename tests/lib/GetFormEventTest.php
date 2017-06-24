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

class GetFormEventTest extends \PHPUnit\Framework\TestCase
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

		$form = null;
		$expected_form = uniqid();
		$event = new GetFormEvent($operation, $request, $form);
		$this->assertSame($request, $event->request);
		$this->assertNull($event->form);
		$event->form = $expected_form;
		$this->assertSame($expected_form, $event->form);
		$this->assertSame($expected_form, $form);
	}
}
