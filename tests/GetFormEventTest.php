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

class GetFormEventTest extends \PHPUnit_Framework_TestCase
{
	public function test_instance()
	{
		$operation = $this
			->getMockBuilder('ICanBoogie\Operation')
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$request = $this
			->getMockBuilder('ICanBoogie\HTTP\Request')
			->disableOriginalConstructor()
			->getMock();

		/* @var $operation \ICanBoogie\Operation */
		/* @var $request \ICanBoogie\HTTP\Request */

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
