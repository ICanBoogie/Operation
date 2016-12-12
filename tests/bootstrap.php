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

use ICanBoogie\Autoconfig\Config;

$autoload = require __DIR__ . '/../vendor/autoload.php';
$autoload->addPsr4('ICanBoogie\Operation\Modules\Sample\\', __DIR__ . '/modules/sample/lib');
$autoload->addPsr4('ICanBoogie\Operation\OperationTest\\', __DIR__ . '/OperationTest');

class Application extends Core
{

}

boot(array_merge_recursive(get_autoconfig(), [

	'config-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'config' => Config::CONFIG_WEIGHT_APP

	],

	'module-path' => [

		__DIR__ . DIRECTORY_SEPARATOR . 'modules'

	]

]));
