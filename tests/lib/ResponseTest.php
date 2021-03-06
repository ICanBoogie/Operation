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

class ResponseTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * @dataProvider provide_test_cases
	 */
	public function test_cases($pathname)
	{
		$response = require $pathname;
		$base = substr($pathname, 0, -4);
		$expected = file_get_contents("$base.header") . file_get_contents("$base.body");
		$expected = str_replace('#{date}', (string) $response->date, $expected);

		$this->assertSame($expected, (string) $response);
	}

	public function provide_test_cases()
	{
		$iterator = new \DirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'cases' . DIRECTORY_SEPARATOR . 'response');
		$iterator = new \RegexIterator($iterator, '#\.php$#');

		$cases = [];

		foreach ($iterator as $item)
		{
			$cases[$item->getFilename()] = [ $item->getPathname() ];
		}

		ksort($cases);

		return array_values($cases);
	}
}
