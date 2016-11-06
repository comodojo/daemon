<?php

$loader = require __DIR__ . "/../vendor/autoload.php";

//$client = \Comodojo\Daemon\Socket\Client::create('unix://test.socket');
$client = \Comodojo\Daemon\Socket\Client::create('unix://daemon.sock');

$data = $client->send('echo', "this is a test message");
$data = $client->send('close', "");

echo "\n\n >> $data << \n\n";

$client->close();
