<?php

require  __DIR__ ."/bootstrap.php";

use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Comodojo\Daemon\Tests\Mock\Daemon;
use \Comodojo\Daemon\Tests\Mock\Worker;

$daemon = new Daemon([], LogManager::create('daemon', false)->getLogger());

$worker = new Worker($daemon->logger, $daemon->events);

$daemon->workers->install($worker, 1);

$worker = new Worker($daemon->logger, $daemon->events);

$daemon->workers->install($worker, 2);

$worker = new Worker($daemon->logger, $daemon->events);

$daemon->workers->install($worker, 3);

$daemon->init();
