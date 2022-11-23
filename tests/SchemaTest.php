<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Tests;

use Devly\WP\Rest\NestedBuilder;
use Devly\WP\Rest\Schema;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SchemaTest extends TestCase
{
    protected Schema $argument;

    public function setUp(): void
    {
        parent::setUp();

        $this->argument = new Schema('foo');
    }

    /**
     * Delete the item after the test.
     */
    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->argument);
    }

    public function testIsString(): void
    {
        $this->argument->isString()->min(10)->max(20);

        $this->assertEquals([
            'type'      => 'string',
            'minLength' => 10,
            'maxLength' => 20,
        ], $this->argument->compile());
    }

    public function testIsStringFormat(): void
    {
        $this->argument->isString('email');

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'email',
        ], $this->argument->compile());
    }

    public function testMatchesRegex(): void
    {
        $this->argument->matchesRegex('[0-9]+');

        $this->assertEquals([
            'type'      => 'string',
            'pattern' => '[0-9]+',
        ], $this->argument->compile());
    }

    public function testIsArray(): void
    {
        $this->argument->isArray(static function (NestedBuilder $nested): void {
            $nested->where('name')->isString();
        })->min(2)->max(4)->unique();

        $this->assertEquals([
            'type'      => 'array',
            'minItems' => 2,
            'maxItems' => 4,
            'uniqueItems' => true,
            'items' => [
                'name' => ['type' => 'string'],
            ],
        ], $this->argument->compile());
    }

    public function testIsArrayOneOf(): void
    {
        $this->argument->isArray(static function (NestedBuilder $arrayItems): void {
            $arrayItems->oneOf(static function (NestedBuilder $oneOfProps): void {
                $oneOfProps->where()->title('Crop')->isObject(static function (NestedBuilder $objProps): void {
                    $objProps->where('operation')->isString()->enum('crop');
                    $objProps->where('x')->isInteger();
                    $objProps->where('y')->isInteger();
                });

                $oneOfProps->where()->title('Rotation')->isObject(static function (NestedBuilder $objProps): void {
                    $objProps->where('operation')->isString()->enum('rotate');
                    $objProps->where('degrees')->isInteger()->min(0)->max(360);
                });
            });
        });

        $this->assertEquals([
            'type'      => 'array',
            'items' => [
                'oneOf' => [
                    [
                        'title'      => 'Crop',
                        'type'       => 'object',
                        'properties' => [
                            'operation' => [
                                'type' => 'string',
                                'enum' => ['crop'],
                            ],
                            'x'         => ['type' => 'integer'],
                            'y'         => ['type' => 'integer'],
                        ],
                    ],
                    [
                        'title'      => 'Rotation',
                        'type'       => 'object',
                        'properties' => [
                            'operation' => [
                                'type' => 'string',
                                'enum' => ['rotate'],
                            ],
                            'degrees'   => [
                                'type'    => 'integer',
                                'minimum' => 0,
                                'maximum' => 360,
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->argument->compile());
    }

    public function testIsObject(): void
    {
        $this->argument->isObject(static function (NestedBuilder $nested): void {
            $nested->where('name')->isString()->required();
            $nested->where('color')->isHexColor()->required();
        })->min(1)->max(3)->additionalProperties(false);

        $this->assertEquals([
            'type'       => 'object',
            'additionalProperties' => false,
            'properties' => [
                'name'  => [
                    'type'     => 'string',
                    'required' => true,
                ],
                'color' => [
                    'type'     => 'string',
                    'format'   => 'hex-color',
                    'required' => true,
                ],
            ],
            'minProperties'        => 1,
            'maxProperties'        => 3,
        ], $this->argument->compile());
    }

    public function testIsObjectWithAdditionalProperties(): void
    {
        $this->argument->isObject()->additionalProperties(static function (NestedBuilder $props): void {
            $props->where()->isObject(static function (NestedBuilder $objProps): void {
                $objProps->where('name')->isString()->required();
                $objProps->where('color')->isHexColor()->required();
            });
        });

        $this->assertEquals([
            'type'       => 'object',
            'properties' => [],
            'additionalProperties' => [
                'type'       => 'object',
                'properties' => [
                    'name'  => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                    'color' => [
                        'type'     => 'string',
                        'format'   => 'hex-color',
                        'required' => true,
                    ],
                ],
            ],
        ], $this->argument->compile());
    }

    public function testIsNumber(): void
    {
        $this->argument->isNumber(10, 20);

        $this->assertEquals([
            'type'    => 'number',
            'minimum' => 10,
            'maximum' => 20,
        ], $this->argument->compile());
    }

    public function testIsUri(): void
    {
        $this->argument->isUri();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'uri',
        ], $this->argument->compile());
    }

    public function testIsNumberMultipleOf(): void
    {
        $this->argument->isMultipleOf(2, 'number');

        $this->assertEquals([
            'type'      => 'number',
            'multipleOf' => 2,
        ], $this->argument->compile());
    }

    public function testIsEmail(): void
    {
        $this->argument->isEmail();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'email',
        ], $this->argument->compile());
    }

    public function testWhereThrowsExceptionWhenNoParentContext(): void
    {
        $this->expectException(RuntimeException::class);

        $this->argument->where('foo');
    }

    public function testIsHexColor(): void
    {
        $this->argument->isHexColor();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'hex-color',
        ], $this->argument->compile());
    }

    public function testIsMultipleOf(): void
    {
        $this->argument->isMultipleOf(2);

        $this->assertEquals([
            'type'      => 'integer',
            'multipleOf' => 2,
        ], $this->argument->compile());
    }

    public function testIsIp(): void
    {
        $this->argument->isIP();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'ip',
        ], $this->argument->compile());
    }

    public function testIsDateTime(): void
    {
        $this->argument->isDateTime();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'date-time',
        ], $this->argument->compile());
    }

    public function testIsUuid(): void
    {
        $this->argument->isUuid();

        $this->assertEquals([
            'type'      => 'string',
            'format' => 'uuid',
        ], $this->argument->compile());
    }

    public function testIsInteger(): void
    {
        $this->argument->isInteger(10, 20);

        $this->assertEquals([
            'type'      => 'integer',
            'minimum' => 10,
            'maximum' => 20,
        ], $this->argument->compile());
    }
}
