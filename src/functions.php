<?php

declare(strict_types=1);

/** phpcs:disable Squiz.Functions.GlobalFunction.Found */

namespace Devly\WP\Rest;

use WP_Error;

/**
 * @param int|string              $code    Error code.
 * @param string                  $message Error message.
 * @param int                     $status  HTTP status code.
 * @param array<array-key, mixed> $data    Response data.
 */
function reject($code = 'unauthorized', string $message = 'Unauthorized', int $status = 401, array $data = []): WP_Error
{
    $data['status'] = $status;

    return new WP_Error($code, $message, $data);
}
