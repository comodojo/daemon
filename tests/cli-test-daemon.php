<?php

require  __DIR__ ."/bootstrap.php";

use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Comodojo\Daemon\Console\LogHandler;
use \Comodojo\Daemon\Tests\Mock\Daemon;
use \Comodojo\Daemon\Tests\Mock\Worker;

$daemon = new Daemon([], LogManager::create('daemon', false)->getLogger());

$logger_1 = $daemon->logger->withName('worker1');
$logger_1->pushHandler(new LogHandler());
$worker_1 = new Worker($logger_1);
$daemon->workers->install($worker_1, 1, true);

$logger_2 = $daemon->logger->withName('worker2');
$logger_2->pushHandler(new LogHandler());
$worker_2 = new Worker($logger_2);
$daemon->workers->install($worker_2, 2, true);

$logger_3 = $daemon->logger->withName('worker3');
$logger_3->pushHandler(new LogHandler());
$worker_3 = new Worker($logger_3);
$daemon->workers->install($worker_3, 3, true);

$daemon->init();
