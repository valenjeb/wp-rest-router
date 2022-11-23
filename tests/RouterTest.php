<?php

declare(strict_types=1);

namespace Devly\WP\Rest\Tests;

use Devly\WP\Rest\Group;
use Devly\WP\Rest\Router;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTestCase;

class RouterTest extends WP_UnitTestCase
{
    protected WP_REST_Server $server;

    public function setUp(): void
    {
        parent::setUp();

        $this->registerRoutes();

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

    public function testPostEndpoint(): void
    {
        $request  = new WP_REST_Request('POST', '/devly/v1/author');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    public function testGetEndpoint(): void
    {
        $request  = new WP_REST_Request('GET', '/devly/v1/author/@john_doe');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    public function testDeleteEndpoint(): void
    {
        $request  = new WP_REST_Request('DELETE', '/devly/v1/author/1');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    public function testPutEndpoint(): void
    {
        $request  = new WP_REST_Request('PUT', '/devly/v1/author/1');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
    }

    public function registerRoutes(): void
    {
        $router = new Router('devly/v1');

        $router->get('/author/{username:\@[\w]+}', static fn () => []);

        $router->group('/author', static function (Group $group): void {
            $group->post('/', static fn () => []);
            $group->put('/{id:d}', static fn () => []);
            $group->delete('/{id:d}', static fn () => []);
        });
    }
}
