<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Devly\WP\Rest\Concerns\HasMiddleware;
use Devly\WP\Rest\Concerns\Routable;

class Group
{
    use Routable;
    use HasMiddleware;

    protected string $prefix;
    protected RoutesCollection $routes;
    protected RoutesCollection $collection;

    /** @param callable|class-string|callable[]|class-string[] $middleware */
    public function __construct(string $pattern, $middleware, RoutesCollection $routes)
    {
        $this->patternPrefix = Helper::processPattern($pattern);
        $this->routes        = $routes;
        $this->middleware    = $middleware;
    }
}
