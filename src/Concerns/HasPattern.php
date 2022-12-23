<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Concerns;

use Devly\Utils\Str;

use function explode;
use function preg_match_all;
use function sprintf;
use function str_replace;

trait HasPattern
{
    protected string $paramRegex = '/(\{[a-zA-Z_\-]+(:.*)?})/';
    /** @var string[] */
    protected array $matchTypes = [
        'i'     => '\d+',
        'd'     => '\d+',
        'a'     => '[A-Za-z]+',
        'alnum' => '[A-Za-z0-9]+',
        'w'     => '\w+',
        ''      => '[-\w]+',
    ];

    public function processPattern(string $pattern): string
    {
        $count = preg_match_all($this->paramRegex, $pattern, $matches);

        if (! $count) {
            return $pattern;
        }

        foreach ($matches[0] as $match) {
            $_match = str_replace(['{', '}'], [''], $match);

            if (Str::contains($_match, ':')) {
                [$key, $regex] = explode(':', $_match);
            } else {
                $key   = $_match;
                $regex = '';
            }

            $regex   = $this->matchTypes[$regex] ?? $regex;
            $pattern = str_replace(
                $match,
                sprintf('(?P<%s>%s)', $key, $regex),
                $pattern,
            );
        }

        return $pattern;
    }
}
