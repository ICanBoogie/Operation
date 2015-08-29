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

$autoload = require __DIR__ . '/../vendor/autoload.php';
$autoload->addPsr4('ICanBoogie\Operation\Modules\Sample\\', __DIR__ . 'modules/sample/lib');

$app = new Core(array_merge_recursive(get_autoconfig(), [

	'config-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'config' => 10

	],

	'module-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'modules'

	]

]));

$app->boot();
