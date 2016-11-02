<?php

$loader = require __DIR__ . "/../vendor/autoload.php";

$client = \Comodojo\Daemon\Socket\Client::create('unix://test.socket');

$data = $client->send('echo', "this is a test message");

echo "\n\n >> $data << \n\n";

$client->close();
