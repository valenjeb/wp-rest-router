<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Devly\DI\Container;
use Devly\DI\Contracts\IContainer;
use Devly\WP\Rest\Concerns\HasMiddleware;
use Devly\WP\Rest\Concerns\Routable;

class Router
{
    use Routable;
    use HasMiddleware;

    /**
     * HTTP methods
     * --------------------------------------- */

    public const ALL_METHODS       = 'GET, POST, PUT, PATCH, DELETE';
    public const EDITABLE_METHODS  = 'POST, PUT, PATCH';
    public const CREATABLE_METHODS = 'POST';
    public const DELETABLE_METHODS = 'DELETE';
    public const READABLE_METHODS  = 'GET';

    /**
     * Action hooks
     * --------------------------------------- */

    public const HOOK_ALTER_ROUTES = 'devly/rest_router/alter_routes';

    protected string $namespace;
    protected IContainer $container;

    public function __construct(string $namespace, ?IContainer $container = null)
    {
        $this->namespace = $namespace;
        $this->container = $container ?? new Container([], true, true);
        $this->routes    = new RoutesCollection($namespace);

        add_action('rest_api_init', fn () => $this->registerRoutes(), 10);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param callable(Group $group): void $routes
     *
     * @return static
     */
    public function group(string $pattern, callable $routes): self
    {
        $routes(new Group($pattern, $this->middleware, $this->routes));

        return $this;
    }

    protected function registerRoutes(): void
    {
        do_action(self::HOOK_ALTER_ROUTES, $this->namespace, $this);

        $this->routes->register($this->container);
    }
}
