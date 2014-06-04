<?php

namespace ICanBoogie\Operation;

$response = new Response;
$response->rc = (object) [

	'one' => 1,
	'two' => 2,
	'three' => (object) [

		1, 2, 3

	]

];

return $response;