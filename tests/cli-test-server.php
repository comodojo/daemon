<?php

$loader = require __DIR__ . "/../vendor/autoload.php";

$logger = new \Monolog\Logger('test');

$server = \Comodojo\Daemon\Socket\Server::create('unix://test.socket', $logger);

$server->commands()->add('echo', function($data) {
    return $data;
});

$server->listen();
