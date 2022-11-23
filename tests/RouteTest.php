<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Tests;

use Closure;
use Devly\DI\Container;
use Devly\WP\Rest\Helper;
use Devly\WP\Rest\Route;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

use function Devly\WP\Rest\reject;

class RouteTest extends WP_UnitTestCase
{
    protected WP_REST_Server $server;

    public function setUp(): void
    {
        parent::setUp();

        add_action('rest_api_init', [$this, 'registerRoutes']);

        $this->server = $GLOBALS['wp_rest_server'] = new WP_REST_Server();
        do_action('rest_api_init');
    }

    /**
     * Delete the item after the test.
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $GLOBALS['wp_rest_server'] = null;
    }

    public function testProcessPattern(): void
    {
        $this->assertEquals('/author/(?P<username>\w+)', Helper::processPattern('/author/{username:w}'));
        $this->assertEquals('/author/(?P<username>[A-Za-z0-9]+)', Helper::processPattern('/author/{username:alnum}'));
        $this->assertEquals('/author/(?P<username>[A-Za-z]+)', Helper::processPattern('/author/{username:a}'));
        $this->assertEquals('/author/(?P<id>\d+)', Helper::processPattern('/author/{id:d}'));
        $this->assertEquals('/author/(?P<username>[-\w]+)', Helper::processPattern('/author/{username}'));
        $this->assertEquals(
            '/author/(?P<username>@[a-zA-Z]+)',
            Helper::processPattern('/author/{username:@[a-zA-Z]+}')
        );
    }

    public function testPermissionsValidationFailure(): void
    {
        $request = new WP_REST_Request('GET', '/devly/v1/author/1');
        $request->set_query_params(['secret' => '1234']);
        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
        $this->assertEquals('Unauthorized', $response->get_data()['message']);
    }

    public function testArgumentsValidationFailure(): void
    {
        wp_set_current_user(1);
        $request = new WP_REST_Request('GET', '/devly/v1/author/1');
        $request->set_query_params(['secret' => 'abc']);
        $response = $this->server->dispatch($request);

        $this->assertEquals(400, $response->get_status());
    }

    public function testValidRoute(): void
    {
        wp_set_current_user(1);
        $request = new WP_REST_Request('GET', '/devly/v1/author/1');
        $request->set_query_params(['secret' => '1234']);
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertEquals('/devly/v1/author/(?P<id>\d+)', $response->get_matched_route());
        $this->assertEquals(['user_id' => 1], $response->get_data());
    }

    public function registerRoutes(): void
    {
        $route = new Route('GET', '/author/{id:d}', static function (int $id): array {
            return ['user_id' => $id];
        });
        $route
            ->middleware(static function (WP_REST_Request $request, Closure $next) {
                if (get_current_user_id() === 0) {
                    return reject();
                }

                return true;
            });
        $route
            ->where('id')->isInteger()
            ->where('secret')->matchesRegex('^[0-9]+$')->required();

        $route->register('devly/v1', new Container());
    }
}
