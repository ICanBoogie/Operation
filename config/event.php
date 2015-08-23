<?php

namespace ICanBoogie\Operation;

use ICanBoogie;

$hooks = Hooks::class . '::';

return [

	ICanBoogie\HTTP\RequestDispatcher::class . '::alter' => $hooks . 'on_alter_request_dispatcher'

];
