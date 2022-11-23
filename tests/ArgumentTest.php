<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Tests;

use Devly\WP\Rest\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testValidateAndSanitize(): void
    {
        $arg = new Argument();
        $arg->validate('__return_true');
        $arg->sanitize(static fn ($value, $request, $key) => (string) $value);

        $this->assertEquals([
            'validate_callback' => '__return_true',
            'sanitize_callback' => static fn ($value, $request, $key) => (string) $value,
        ], $arg->compile());
    }
}
