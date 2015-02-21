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

class PingOperationTest extends \PHPUnit_Framework_TestCase
{
	public function test_process()
	{
		$operation = new PingOperation;
		$response = $operation(Request::from('/api/core/ping'));
		$this->assertEquals("pong", $response->rc);
		$response = $operation(Request::from('/api/core/ping?timer'));
		$this->assertStringStartsWith("pong, in", $response->rc);
	}
}
