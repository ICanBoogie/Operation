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

class FormHasExpiredTest extends \PHPUnit\Framework\TestCase
{
	public function test_instance()
	{
		$instance = new FormHasExpired;
		$this->assertNotEmpty($instance->getMessage());
		$this->assertEquals(500, $instance->getCode());
	}
}
