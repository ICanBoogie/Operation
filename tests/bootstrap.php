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

use function ob_start;

chdir(__DIR__);

$autoload = require __DIR__ . '/../vendor/autoload.php';
$autoload->addPsr4('ICanBoogie\Operation\Modules\Sample\\', __DIR__ . '/modules/sample/lib');
$autoload->addPsr4('ICanBoogie\Operation\OperationTest\\', __DIR__ . '/lib/OperationTest');

class Application extends ApplicationAbstract
{

}

boot();

ob_start(); // Prevents PHPUnit from sending headers
