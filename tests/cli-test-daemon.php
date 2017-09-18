#!/usr/bin/env php
<?php

require  __DIR__ ."/bootstrap.php";

use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Comodojo\Daemon\Console\LogHandler;
use \Comodojo\Daemon\Tests\Mock\Daemon;
use \Comodojo\Daemon\Tests\Mock\Worker;

$daemon = new Daemon([
    // 'socketmaxconnections' => 2,
    'sockethandler' => 'tcp://127.0.0.1:10042'
], LogManager::create('daemon', false)->getLogger());

// $worker_1 = new Worker("target_worker");
// $daemon->getWorkers()->install($worker_1, 1, true);
//
// $worker_2 = new Worker();
// $daemon->getWorkers()->install($worker_2, 2, true);
//
// $worker_3 = new Worker();
// $daemon->getWorkers()->install($worker_3, 1, false);

$daemon->init();
