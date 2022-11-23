<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use WP_Error;
use WP_REST_Response;

abstract class Controller
{
    /**
     * @param mixed                 $data    Response data
     * @param int                   $status  HTTP status code.
     * @param array<string, string> $headers HTTP header map.
     */
    protected function sendResponse($data = null, int $status = 200, array $headers = []): WP_REST_Response
    {
        return new WP_REST_Response($data, $status, $headers);
    }

    /**
     * Send error response
     *
     * @param string|int $code    Error code
     * @param string     $message Error message.
     * @param int        $status  HTTP status code.
     * @param mixed      $data    Error data.
     */
    protected function sendError($code, string $message, int $status = 500, $data = ''): WP_Error
    {
        if (! empty($data)) {
            $data = ['status' => $status];
        } else {
            $data           = (array) $data;
            $data['status'] = $status;
        }

        return new WP_Error($code, $message, $data);
    }
}
