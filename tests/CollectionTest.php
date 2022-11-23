<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Tests;

use Devly\Exceptions\RouteNotFoundException;
use Devly\WP\Rest\RoutesCollection;
use WP_UnitTestCase;

class CollectionTest extends WP_UnitTestCase
{
    public function testAddAndGetRoute(): void
    {
        $collection = new RoutesCollection('devly/v1');

        $route = $collection->addRoute('POST', '/foo', static fn () => ['foo' => 'bar'])
            ->name('foo');

        $this->assertTrue($collection->has('foo'));
        $this->assertFalse($collection->has('bar'));
        $this->assertEquals($route, $collection->get('foo'));
    }

    public function testGetRouteThrowsExceptionIfRouteDoesNotExist(): void
    {
        $this->expectException(RouteNotFoundException::class);

        $collection = new RoutesCollection('devly/v1');

        $collection->get('foo');
    }
}
