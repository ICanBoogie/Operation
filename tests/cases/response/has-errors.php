<?php

namespace ICanBoogie\Operation;

$response = new Response;

$errors = $response->errors;

$errors->add_generic("A global error");
$errors->add('title', "An error on the %field field.", [ 'field' => 'Title']);
$errors->add('password'); // an error on 'password', without message
$errors->add('message', "Message too long");
$errors->add('message', "Message too short");

$response->status = 400;

return $response;
