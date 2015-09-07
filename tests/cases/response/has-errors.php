<?php

namespace ICanBoogie\Operation;

$response = new Response;

$errors = $response->errors;

$errors->add(null, 'A global error');
$errors->add('title', "An error on the %field field.", [ 'field' => 'Title']);
$errors['password'] = true; // an error on 'password', without message
$errors['message'] = "Message too long";
$errors['message'] = "Message too short";

$response->status = 400;

return $response;
