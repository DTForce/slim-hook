<?php

use App\Controller;
use App\Executor;
use App\Handler;
use Interop\Container\ContainerInterface;


$container = $app->getContainer();

$container[Controller::class] = function (ContainerInterface $c) {
	return new Controller($c, $c->get(Handler::class));
};

$container[Handler::class] = function (ContainerInterface $c) {
	return new Handler($c, $c->get(Executor::class));
};

$container[Executor::class] = function (ContainerInterface $c) {
	return new Executor($c);
};

// monolog
$container['logger'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler(eval($settings['path']), $settings['level']));
    return $logger;
};
