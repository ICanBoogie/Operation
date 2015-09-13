<?php

namespace ICanBoogie\Operation;

$response = new Response;
$response->content_type = "text/plain; charset=utf-8";
$response->content_length = filesize(__FILE__);

$response->rc = function($response) {

	echo file_get_contents(__FILE__);

};

return $response;
