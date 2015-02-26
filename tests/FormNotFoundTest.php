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

class FormNotFoundTest extends \PHPUnit_Framework_TestCase
{
	public function test_instance()
	{
		$operation = $this
			->getMockBuilder('ICanBoogie\Operation')
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		/* @var $operation \ICanBoogie\Operation */

		$instance = new FormNotFound($operation);
		$this->assertSame($operation, $instance->operation);
		$this->assertNotEmpty($instance->getMessage());
		$this->assertEquals(500, $instance->getCode());
	}
}