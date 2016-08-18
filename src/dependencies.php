<?php
// DIC configuration

use App\Controller;


$container = $app->getContainer();

$container[Controller::class] = function ($c) {
	return new Controller($c);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler(eval($settings['path']), $settings['level']));
    return $logger;
};
