<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use RuntimeException;

use function is_array;
use function is_object;
use function method_exists;

class Helper
{
    /**
     * @param array<array-key, mixed>|object $props
     *
     * @return array<array-key, mixed>
     */
    public static function parseProperties($props): array
    {
        if (is_array($props)) {
            return $props;
        }

        if (! is_object($props) || ! method_exists($props, 'compile')) {
            throw new RuntimeException('Provided properties item is not compilable.');
        }

        return $props->compile();
    }
}
