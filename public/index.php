<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
	$_SERVER['SCRIPT_NAME'] = '/' . basename(__FILE__); // work around of - https://github.com/slimphp/Slim/issues/359
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
define('CONFIG_DIR', __DIR__ . '/../config');
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();
