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
			$rc .= ', in ' . number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 3, '.', '') . ' ms.';
		}

		return $rc;
	}
}
