<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Devly\DI\Contracts\IContainer;
use Devly\Utils\Pipeline;
use Devly\WP\Rest\Concerns\HasMiddleware;
use Devly\WP\Rest\Concerns\HasPattern;
use WP_REST_Request;

use function explode;
use function is_array;
use function is_callable;
use function is_string;
use function md5;
use function strpos;
use function substr;

class Route
{
    use HasMiddleware;
    use HasPattern;

    protected string $methods;
    protected string $pattern;
    /** @var callable|class-string|string */
    protected $controller;
    /** @var Schema[] */
    protected array $wheres = [];
    protected string $name;

    protected Schema $schema;

    /** @param callable|class-string|string $controller */
    public function __construct(string $method, string $pattern, $controller)
    {
        $this->methods    = $method;
        $this->pattern    = $this->processPattern($pattern);
        $this->controller = $controller;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }

        return $this->name = $this->generateUniqueRouteID($this->pattern);
    }

    protected function generateUniqueRouteID(string $pattern): string
    {
        return substr(md5($pattern), 0, 6);
    }

    public function register(string $namespace, IContainer $container): void
    {
        $args = [
            'methods'             => $this->methods,
            'callback'            => function (WP_REST_Request $request) use ($container) {
                $container->instance(WP_REST_Request::class, $request);

                $controller = $this->controller;
                if (is_callable($controller)) {
                    $response = $container->call($controller, $request->get_params());

                    return rest_ensure_response($response);
                }

                if (is_string($controller) && strpos($controller, '::') !== false) {
                    $controller = explode('::', $controller);
                }

                if (is_array($controller)) {
                    [$class, $method] = $controller;
                } else {
                    $class  = $controller;
                    $method = 'run';
                }

                $object   = $container->make($class);
                $response = $container->call([$object, $method], $request->get_params());

                return rest_ensure_response($response);
            },
            'permission_callback' => empty($this->middleware)
                ? '__return_true'
                : function (WP_REST_Request $request) use ($container) {
                    $container->instance(WP_REST_Request::class, $request);

                    return Pipeline::create($container)
                        ->send($container[WP_REST_Request::class])
                        ->through($this->middleware)
                        ->then(static fn () => true);
                },
            'args'                => $this->compileArgs(),
        ];

        if (isset($this->schema)) {
            $args = [
                $args,
                'schema' => $this->schema->compile(), // phpcs:ignore Squiz.Arrays.ArrayDeclaration.KeySpecified
            ];
        }

        register_rest_route($namespace, $this->pattern, $args);
    }

    /** @return array<string, array<string, mixed>> */
    protected function compileArgs(): array
    {
        $args = [];
        foreach ($this->wheres as $key => $arg) {
            if (is_string($key)) {
                $args[$key] = $arg->compile();
            } else {
                $args[] = $arg->compile();
            }
        }

        return $args;
    }

    /**
     * Set argument validation schema
     *
     * @param string|string[] $primitiveOrRegex Primitive type, list of types, or regex pattern
     */
    public function where(string $key, $primitiveOrRegex = null): Argument
    {
        $arg = new Argument($key, $primitiveOrRegex, $this);

        $this->wheres[$key] = $arg;

        return $arg;
    }

    public function schema(?string $version = null): Schema
    {
        $schema       = new Schema();
        $this->schema = $schema->schema($version ?: 'http://json-schema.org/draft-04/schema#');

        return $this->schema;
    }
}
