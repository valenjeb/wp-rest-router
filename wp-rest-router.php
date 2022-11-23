<?php

/**
 * Plugin Name:     WP REST API Router
 * Plugin URI:      https://github.com/valenjeb/wp-rest-router
 * Description:     A fluent router for registering and managing WordPress REST API routes & endpoints in an OOP way.
 * Author:          Valentin Jebelev
 * Author URI:      https://github.com/valenjeb
 * Text Domain:     wp-rest-router
 * Domain Path:     /languages
 * Version:         0.1.0
 */

declare(strict_types=1);

$autoload = dirname(__FILE__) . '/vendor/autoload.php';

if (! file_exists($autoload)) {
    return;
}

require_once $autoload;
