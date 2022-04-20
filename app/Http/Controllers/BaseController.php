<?php

namespace App\Http\Controllers;

class BaseController
{
    /**
     * Get params from request
     *
     * @return array|false|string
     */
	protected function getParams()
	{
		if (!empty($_REQUEST)) {
			return $_REQUEST;
		}

		$body = file_get_contents('php://input');

		if (empty($body)) {
		    return [];
        }

        $params = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $params;
	}

	
	/**
	 * Send API output.
	 *
	 * @param mixed $data
	 * @param string $httpStatusCode
	 */
	protected function sendOutput($data, $httpStatusCode = 200)
	{
		header_remove('Set-Cookie');
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");

        if (is_array($data)) {
			$data = json_encode($data);
		}

        http_response_code($httpStatusCode);
		echo $data;
		exit;
	}
}