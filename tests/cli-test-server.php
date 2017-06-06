#!/usr/bin/env php
<?php

//$loader = require __DIR__ . "/../vendor/autoload.php";

require  __DIR__ ."/bootstrap.php";

$logger = new \Monolog\Logger('test');

$events = new \Comodojo\Foundation\Events\Manager;

$process = new \Comodojo\Daemon\Tests\Mock\Process(null, $logger, $events);

$server = \Comodojo\Daemon\Socket\Server::create('unix://daemon.sock', $logger, $events, $process);

$server->commands->add('echo', function($data) {
    return $data;
});

$server->listen();
