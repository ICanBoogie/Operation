<?php

namespace ICanBoogie\Operation;

$response = new Response;
$response->rc = [

	'one' => 1,
	'two' => 2,
	'three' => [

		1, 2, 3

	]

];

return $response;