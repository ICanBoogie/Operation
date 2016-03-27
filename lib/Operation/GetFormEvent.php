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

use ICanBoogie\Event;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

/**
 * Event class for the `ICanBoogie\Operation::get_form` event.
 */
class GetFormEvent extends Event
{
	const TYPE = 'get_form';

	/**
	 * Reference to the result variable.
	 *
	 * @var mixed
	 */
	public $form;

	/**
	 * The request that triggered the operation.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `get_form`.
	 *
	 * @param Operation $target
	 * @param Request $request
	 * @param mixed $form
	 */
	public function __construct(Operation $target, Request $request, &$form)
	{
		$this->request = $request;
		$this->form = &$form;

		parent::__construct($target, self::TYPE);
	}
}
