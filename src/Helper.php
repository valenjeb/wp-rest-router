<?php

declare(strict_types=1);

namespace Devly\WP\Rest;

use Devly\Utils\Str;
use RuntimeException;

use function explode;
use function is_array;
use function is_object;
use function method_exists;
use function preg_match_all;
use function sprintf;
use function str_replace;

class Helper
{
    protected static string $paramRegex = '/(\{[a-zA-Z_\-]+(:.*)?})/';
    /** @var string[] */
    protected static array $matchTypes = [
        'i'     => '\d+',
        'd'     => '\d+',
        'a'     => '[A-Za-z]+',
        'alnum' => '[A-Za-z0-9]+',
        'w'     => '\w+',
        ''      => '[-\w]+',
    ];

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

    public static function processPattern(string $pattern): string
    {
        $count = preg_match_all(self::$paramRegex, $pattern, $matches);

        if (! $count) {
            return $pattern;
        }

        foreach ($matches[0] as $match) {
            $match = str_replace(['{', '}'], [''], $match);

            if (Str::contains($match, ':')) {
                [$key, $regex] = explode(':', $match);
            } else {
                $key   = $match;
                $regex = '';
            }

            $regex   = self::$matchTypes[$regex] ?? $regex;
            $pattern = str_replace(
                '{' . $match . '}',
                sprintf('(?P<%s>%s)', $key, $regex),
                $pattern,
            );
        }

        return $pattern;
    }
}
