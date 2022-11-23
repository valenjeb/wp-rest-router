<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use WP_REST_Request;

class Argument extends Schema
{
    /**
     * @param callable(mixed $value, WP_REST_Request $req, string $key): mixed $callback Should return true or WP_Error
     *                                                                                   to reject the request.
     *
     * @return static
     */
    public function validate(callable $callback): self
    {
        return $this->set('validate_callback', $callback);
    }

    /**
     * @param callable(mixed $value, WP_REST_Request $req, string $key): mixed $callback Should return the sanitized
     *                                                                                   value or WP_Error.
     *
     * @return static
     */
    public function sanitize(callable $callback): self
    {
        return $this->set('sanitize_callback', $callback);
    }
}
