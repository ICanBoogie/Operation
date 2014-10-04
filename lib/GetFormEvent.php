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

/**
 * Event class for the `ICanBoogie\Operation::get_form` event.
 */
class GetFormEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the result variable.
	 *
	 * @var mixed
	 */
	public $form;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `get_form`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, Request $request, &$form)
	{
		$this->request = $request;
		$this->form = &$form;

		parent::__construct($target, 'get_form');
	}
}