<?php

namespace ICanBoogie\Operation;

$response = new Response;

$errors = $response->errors;

$errors[] = $errors->format('A global error');
$errors['title'] = $errors->format('An error on the %field field.', [ 'field' => 'Title']);
$errors['password'] = true; // an error on 'password', without message
$errors['message'] = "Message too long";
$errors['message'] = "Message too short";

$response->status = 400;

return $response;
