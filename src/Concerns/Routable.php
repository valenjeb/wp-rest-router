<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Concerns;

use Devly\WP\Rest\Route;
use Devly\WP\Rest\Router;
use Devly\WP\Rest\RoutesCollection;

use function array_map;
use function implode;
use function is_string;
use function strtoupper;

trait Routable
{
    protected RoutesCollection $routes;
    protected string $patternPrefix = '';

    /**
     * Add a new route with the given HTTP methods
     *
     * @param string|string[]                                          $methods
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function match($methods, string $pattern, $callback): Route
    {
        if (is_string($methods)) {
            $methods = strtoupper($methods);
        } else {
            $methods = array_map('strtoupper', $methods);
            $methods = implode(', ', $methods);
        }

        return $this->addRoute($methods, $pattern, $callback);
    }

    /**
     * Add a route responding to all HTTP methods.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function any(string $pattern, $callback): Route
    {
        return $this->addRoute(Router::ALL_METHODS, $pattern, $callback);
    }

    /**
     * Add a GET route.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function get(string $pattern, $callback): Route
    {
        return $this->addRoute('GET', $pattern, $callback);
    }

    /**
     * Add a POST route.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function post(string $pattern, $callback): Route
    {
        return $this->addRoute('POST', $pattern, $callback);
    }

    /**
     * Add a PUT route.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function put(string $pattern, $callback): Route
    {
        return $this->addRoute('PUT', $pattern, $callback);
    }

    /**
     * Add a PATCH route.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function patch(string $pattern, $callback): Route
    {
        return $this->addRoute('PATCH', $pattern, $callback);
    }

    /**
     * Add a DELETE route.
     *
     * @param callable|class-string|string|array<class-string, string> $callback
     */
    public function delete(string $pattern, $callback): Route
    {
        return $this->addRoute('DELETE', $pattern, $callback);
    }

    /** @param callable|class-string|string|array<class-string, string> $callback */
    public function addRoute(string $method, string $pattern, $callback): Route
    {
        if ($pattern === '/') {
            $pattern = '';
        }

        $route = $this->routes->addRoute($method, $this->patternPrefix . $pattern, $callback);
        $route->middleware($this->middleware);

        return $route;
    }

    public function hasRoute(string $name): bool
    {
        return $this->routes->has($name);
    }

    public function getRoute(string $name): Route
    {
        return $this->routes->get($name);
    }
}
