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

$loader = require __DIR__ . '/../vendor/autoload.php';

$loader->addClassMap([

	'ICanBoogie\Operation\Modules\Sample\Module' =>                       __DIR__ . '/modules/sample/module.php',
	'ICanBoogie\Operation\Modules\Sample\SuccessOperation' =>             __DIR__ . '/modules/sample/lib/operations/success.php',
	'ICanBoogie\Operation\Modules\Sample\SuccessWithLocationOperation' => __DIR__ . '/modules/sample/lib/operations/success_with_location.php',
	'ICanBoogie\Operation\Modules\Sample\FailureOperation' =>             __DIR__ . '/modules/sample/lib/operations/failure.php',
	'ICanBoogie\Operation\Modules\Sample\ErrorOperation' =>               __DIR__ . '/modules/sample/lib/operations/error.php',
	'ICanBoogie\Operation\Modules\Sample\ExceptionOperation' =>           __DIR__ . '/modules/sample/lib/operations/exception.php',
	'ICanBoogie\Operation\Modules\Sample\ExpiredOperation' =>             __DIR__ . '/modules/sample/lib/operations/expired.php',
	'ICanBoogie\Operation\Modules\Sample\OnlineOperation' =>              __DIR__ . '/modules/sample/lib/operations/online.php'

]);

$app = new Core(array_merge_recursive(get_autoconfig(), [

	'config-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'config' => 10

	],

	'module-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'modules'

	]

]));

$app->boot();
