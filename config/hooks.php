<?php

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\RequestDispatcher;

$hooks = Hooks::class . '::';

return [

	'events' => [

		RequestDispatcher::class . '::alter' => $hooks . 'on_alter_request_dispatcher'

	]

];
