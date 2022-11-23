<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Concerns;

use function is_array;

trait HasMiddleware
{
    /** @var callable[]|class-string[] */
    protected array $middleware = [];

    /**
     * @param callable|class-string|array<callable|class-string> $middleware
     *
     * @return static
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            foreach ($middleware as $cb) {
                $this->middleware($cb);
            }
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }
}
