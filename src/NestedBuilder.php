<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Closure;

use function is_callable;
use function is_int;

class NestedBuilder
{
    /** @var array<array-key, Schema> */
    protected array $wheres = [];

    /**
     * Set argument validation schema
     *
     * @param null $primitiveOrRegex Primitive type, list of types, or regex pattern
     */
    public function where(?string $key = null, $primitiveOrRegex = null): Schema
    {
        $prop = new Schema($key, $primitiveOrRegex, $this);

        if (! empty($key)) {
            $this->wheres[$key] = $prop;
        } else {
            $this->wheres[] = $prop;
        }

        return $prop;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function default($value): self
    {
        $this->wheres['default'] = $value;

        return $this;
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed> $options
     *
     * @static
     */
    public function oneOf($options): self
    {
        return $this->someOf('oneOf', $options);
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed> $options
     *
     * @static
     */
    public function anyOf($options): self
    {
        return $this->someOf('anyOf', $options);
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed> $options
     *
     * @static
     */
    public function allOf($options): self
    {
        return $this->someOf('allOf', $options);
    }

    /** @return array<array-key, mixed> */
    public function compile(): array
    {
        $args = [];
        foreach ($this->wheres as $key => $arg) {
            if (is_int($key)) {
                $args[] = Helper::parseProperties($arg);

                continue;
            }

            $args[$key] = Helper::parseProperties($arg);
        }

        return $args;
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed> $options
     *
     * @return static
     */
    protected function someOf(string $type, $options): self
    {
        if (is_callable($options)) {
            $builder = new self();
            $options($builder);

            $this->wheres[$type] = $builder->compile();

            return $this;
        }

        $this->wheres[$type] = (array) $options;

        return $this;
    }
}
