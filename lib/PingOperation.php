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

use ICanBoogie\Errors;
use ICanBoogie\Operation;
use ICanBoogie\Session;

/**
 * Keeps the user's session alive.
 *
 * Only already created sessions are kept alive, new sessions will *not* be created.
 */
class PingOperation extends Operation
{
	static private function format_time($finish)
	{
		return number_format(($finish - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3, '.', '') . ' ms';
	}

	protected function validate(Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		$this->response->content_type = 'text/plain';

		if (Session::exists())
		{
			$this->app->session;
		}

		$rc = 'pong';

		if ($this->request['timer'] !== null)
		{
			$boot_time = self::format_time($_SERVER['ICANBOOGIE_READY_TIME_FLOAT']);
			$run_time = self::format_time(microtime(true));

			$rc .= ", in $run_time (ready in $boot_time)";
		}

		return $rc;
	}
}
