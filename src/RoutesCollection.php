<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Devly\DI\Contracts\IContainer;
use Devly\Exceptions\RouteNotFoundException;

use function array_key_exists;
use function sprintf;

class RoutesCollection
{
    protected string $namespace;
    /** @var Route[] */
    protected array $routes = [];
    /** @var Route[] */
    protected array $namedRoutes = [];

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    public function register(IContainer $container): void
    {
        foreach ($this->routes as $route) {
            $route->register($this->namespace, $container);
        }
    }

    /**
     * @param callable|class-string<T>|string|array<class-string<T>|T, string> $callback
     *
     * @template T of object
     */
    public function addRoute(string $method, string $pattern, $callback): Route
    {
        $route = new Route($method, $pattern, $callback);

        $this->routes[] = $route;

        return $route;
    }

    public function has(string $name): bool
    {
        if (array_key_exists($name, $this->namedRoutes)) {
            return true;
        }

        foreach ($this->routes as $i => $route) {
            $this->namedRoutes[$route->getName()] = $route;
            unset($this->routes[$i]);

            if ($route->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get route from the collection by its name
     *
     * @throws RouteNotFoundException
     */
    public function get(?string $name = null): Route
    {
        if (! $this->has($name)) {
            throw new RouteNotFoundException(sprintf(
                'Route "%s" not found in namespace "%s".',
                $name,
                $this->namespace
            ));
        }

        return $this->namedRoutes[$name];
    }
}
