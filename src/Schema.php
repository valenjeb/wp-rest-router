<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Closure;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

use function func_get_args;
use function func_num_args;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function sprintf;

class Schema
{
    public const PRIMITIVES = [
        self::STRING,
        self::INTEGER,
        self::NUMBER,
        self::BOOL,
        self::INT,
        self::ARRAY,
        self::OBJECT,
        self::NULL,
    ];
    public const STRING     = 'string';
    public const INTEGER    = 'integer';
    public const NUMBER     = 'number';
    public const INT        = 'integer';
    public const BOOL       = 'boolean';
    public const ARRAY      = 'array';
    public const OBJECT     = 'object';
    public const NULL       = 'null';

    /** @var array<string, mixed> */
    protected array $properties = [];
    protected ?object $parentContext;
    protected ?string $name;

    /** @param string|string[] $primitiveOrRegex Primitive type, list of types, or regex pattern */
    public function __construct(?string $name = null, $primitiveOrRegex = null, ?object $parentContext = null)
    {
        $this->name          = $name;
        $this->parentContext = $parentContext;

        if (empty($primitiveOrRegex)) {
            return;
        }

        if (is_array($primitiveOrRegex)) {
            $this->type($primitiveOrRegex);

            return;
        }

        try {
            $this->type($primitiveOrRegex);

            return;
        } catch (InvalidArgumentException $e) {
        }

        $this->matchesRegex($primitiveOrRegex);
    }

    /** @return static */
    public function schema(string $schema): self
    {
        return $this->set('$schema', $schema);
    }

    /**
     * @param string|string[] $context
     *
     * @return static
     */
    public function context($context): self
    {
        return $this->set('context', is_array($context) ? $context : func_get_args());
    }

    public function isReadOnly(): self
    {
        return $this->set('readonly', true);
    }

    /** @return static */
    public function id(string $id): self
    {
        return $this->set('id', $id);
    }

    /** @return static */
    public function title(string $title): self
    {
        return $this->set('title', $title);
    }

    /** @return static */
    public function required(bool $required = true): self
    {
        return $this->set('required', $required);
    }

    /**
     * @param string|string[] $type,...
     *
     * @return static
     */
    public function type($type): self
    {
        if (func_num_args() > 1) {
            $type = func_get_args();
        }

        if (is_array($type)) {
            foreach ($type as $t) {
                $this->type($t);
            }

            return $this;
        }

        if (! in_array($type, self::PRIMITIVES)) {
            throw new InvalidArgumentException(sprintf(
                'Property type "%s" is not supported. Supported types are: %s.',
                $type,
                implode(', ', self::PRIMITIVES)
            ));
        }

        if ($this->getType()) {
            $old = (array) $this->getType();
            $new = (array) $type;

            $type = [...$old, ...$new];
        }

        return $this->set('type', $type);
    }

    public function description(string $description): self
    {
        return $this->set('description', $description);
    }

    /** @return static */
    public function isString(?string $format = null): self
    {
        $this->set('type', self::STRING);

        if (! empty($format)) {
            $this->set('format', $format);
        }

        return $this;
    }

    /** @return static */
    public function isNumber(?int $min = null, ?int $max = null): self
    {
        $this->set('type', self::NUMBER);

        if ($min !== null) {
            $this->set('minimum', $min);
        }

        if ($max !== null) {
            $this->set('maximum', $max);
        }

        return $this;
    }

    /** @return static */
    public function isInteger(?int $min = null, ?int $max = null): self
    {
        $this->set('type', self::INTEGER);

        if ($min !== null) {
            $this->set('minimum', $min);
        }

        if ($max !== null) {
            $this->set('maximum', $max);
        }

        return $this;
    }

    /**
     * Assert an integer or number type is a multiple of the given number.
     *
     * multipleOf also supports decimals. For example, this schema could
     * be used to accept a percentage with a maximum of 1 decimal point.
     *
     * @param self::INTEGER|self::NUMBER $type
     *
     * @return static
     */
    public function isMultipleOf(int $number, string $type = self::INTEGER): self
    {
        if ($type === self::NUMBER) {
            $this->isNumber();
        } elseif ($type === self::INTEGER) {
            $this->isInteger();
        } else {
            throw new InvalidArgumentException(sprintf(
                'The #2 %s::%s() method argument expects "%s" or "%s". Provided "%s".',
                static::class,
                __METHOD__,
                self::INTEGER,
                self::NUMBER,
                $type
            ));
        }

        return $this->set('multipleOf', $number);
    }

    /** @return static */
    public function isBool(): self
    {
        return $this->type(self::BOOL);
    }

    /** @return static */
    public function isEmail(): self
    {
        return $this->isString('email');
    }

    /** @return static */
    public function isDateTime(): self
    {
        return $this->isString('date-time');
    }

    /** @return static */
    public function isUri(): self
    {
        return $this->isString('uri');
    }

    /** @return static */
    public function isIP(): self
    {
        return $this->isString('ip');
    }

    /** @return static */
    public function isUuid(): self
    {
        return $this->isString('uuid');
    }

    /** @return static */
    public function isHexColor(): self
    {
        return $this->isString('hex-color');
    }

    /** @return static */
    public function matchesRegex(string $pattern): self
    {
        $this->isString();

        return $this->set('pattern', $pattern);
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, string> $arrayOrCallback
     *
     * @return static
     */
    public function isArray($arrayOrCallback): self
    {
        $this->type(self::ARRAY);

        if (is_array($arrayOrCallback)) {
            return $this->set('items', $arrayOrCallback);
        }

        if (! is_callable($arrayOrCallback)) {
            throw new InvalidArgumentException();
        }

        $builder = new NestedBuilder();
        $arrayOrCallback($builder);

        return $this->set('items', $builder->compile());
    }

    /** @return static */
    public function unique(): self
    {
        if ($this->getType() !== self::ARRAY) {
            throw new LogicException(sprintf(
                'The %s::%s() method can be used with array argument only.',
                self::class,
                __METHOD__
            ));
        }

        return $this->set('uniqueItems', true);
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed> $arrayOrCallback
     *
     * @return static
     */
    public function isObject($arrayOrCallback = []): self
    {
        $this->type(self::OBJECT);

        if (is_array($arrayOrCallback)) {
            return $this->set('properties', $arrayOrCallback);
        }

        if (! is_callable($arrayOrCallback)) {
            throw new InvalidArgumentException();
        }

        $builder = new NestedBuilder();
        $arrayOrCallback($builder);

        return $this->set('properties', $builder->compile());
    }

    /**
     * @param Closure(NestedBuilder): void|array<array-key, mixed>|bool $properties
     *
     * @return static
     */
    public function additionalProperties($properties): self
    {
        if (! $this->getType() || $this->getType() !== self::OBJECT) {
            throw new LogicException(sprintf(
                'The %s::%s() method can only be used with argument of type %s.',
                self::class,
                __METHOD__,
                self::OBJECT
            ));
        }

        if (is_bool($properties) || is_array($properties)) {
            return $this->set('additionalProperties', $properties);
        }

        $builder = new NestedBuilder();
        $properties($builder);

        return $this->set('additionalProperties', $builder->compile()[0]);
    }

    /** @return static */
    public function min(int $min, bool $exclusive = false): self
    {
        if (! $this->getType()) {
            throw new LogicException(sprintf(
                'The %s::%s() method can not be used before the argument type is set.',
                self::class,
                __METHOD__
            ));
        }

        switch ($this->getType()) {
            case self::ARRAY:
                $this->properties['minItems'] = $min;
                break;
            case self::OBJECT:
                $this->properties['minProperties'] = $min;
                break;
            case self::STRING:
                $this->properties['minLength'] = $min;
                break;
            case self::INTEGER:
            case self::NUMBER:
                $this->properties['minimum'] = $min;
                if ($exclusive) {
                    $this->properties['exclusiveMinimum'] = true;
                }

                break;
            default:
                throw new LogicException(sprintf(
                    'The %s::%s() method can only be used with argument of type %s, %s, %s, %s and %s.',
                    self::class,
                    __METHOD__,
                    self::INTEGER,
                    self::NUMBER,
                    self::STRING,
                    self::ARRAY,
                    self::OBJECT
                ));
        }

        return $this;
    }

    /** @return static */
    public function max(int $max, bool $exclusive = false): self
    {
        if (! $this->getType()) {
            throw new LogicException(sprintf(
                'The %s::%s() method can not be used before the argument type is set.',
                self::class,
                __METHOD__
            ));
        }

        switch ($this->getType()) {
            case self::ARRAY:
                $this->properties['maxItems'] = $max;
                break;
            case self::OBJECT:
                $this->properties['maxProperties'] = $max;
                break;
            case self::STRING:
                $this->properties['maxLength'] = $max;
                break;
            case self::INTEGER:
            case self::NUMBER:
                $this->properties['maximum'] = $max;
                if ($exclusive) {
                    $this->properties['exclusiveMaximum'] = true;
                }

                break;
            default:
                throw new LogicException(sprintf(
                    'The %s::%s() method can only be used with argument of type %s, %s, %s, %s and %s.',
                    self::class,
                    __METHOD__,
                    self::INTEGER,
                    self::NUMBER,
                    self::STRING,
                    self::ARRAY,
                    self::OBJECT
                ));
        }

        return $this;
    }

    /**
     * @param string|int|array<string|int> $items,...
     *
     * @return static
     */
    public function enum($items): self
    {
        $items = is_array($items) ? $items : func_get_args();

        return $this->set('enum', $items);
    }

    /** @return static */
    public function ref(string $ref): self
    {
        return $this->set('$ref', $ref);
    }

    /**
     * Chain another argument definition.
     *
     * @param string|string[]|null $primitiveOrPattern Primitive type, list of types, or regex pattern
     *
     * @return static
     */
    public function where(?string $name, $primitiveOrPattern = null): self
    {
        if (! isset($this->parentContext)) {
            throw new RuntimeException('Parent context was not set.');
        }

        return $this->parentContext->where($name, $primitiveOrPattern);
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function default($value): self
    {
        $this->properties['default'] = $value;

        return $this;
    }

    /** @return array<string, mixed> */
    public function compile(): array
    {
        return $this->properties;
    }

    /** @return mixed */
    protected function getType()
    {
        return $this->properties['type'] ?? null;
    }

    public function getKey(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    protected function set(string $key, $value): self
    {
        $this->properties[$key] = $value;

        return $this;
    }
}
