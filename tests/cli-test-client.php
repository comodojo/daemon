<?php

use \Comodojo\Foundation\Utils\UniqueId;
use \Comodojo\Daemon\Socket\SocketTransport;
use \Comodojo\RpcClient\RpcClient;
use \Comodojo\RpcClient\RpcRequest;

$loader = require __DIR__ . "/../vendor/autoload.php";

//$client = \Comodojo\Daemon\Socket\Client::create('unix://daemon.sock');
//$client = \Comodojo\Daemon\Socket\Client::create('tcp://127.0.0.1:10042');

// $client2 = \Comodojo\Daemon\Socket\Client::create('tcp://127.0.0.1:10042');

//sleep(10);

$transport = SocketTransport::create('tcp://127.0.0.1:10042');
$rpc_client = new RpcClient('tcp://127.0.0.1:10042', null, $transport);

// $request = RpcRequest::create("test.echo", ["This is a test"]);
// $request = RpcRequest::create("system.listMethods");
// $request = RpcRequest::create("system.methodSignature", ["test.echo"]);
$request = RpcRequest::create("test.echo", [UniqueId::generate(1024*512)]);

$rpc_client->addRequest($request);

$data = $rpc_client->send();

// $data = $client->send('echo', "this is a test message");

// $data2 = $client->send('echo', "this is a test message (2)");
// $data = $client->send('close', "");

// $data = $client->send('echo', UniqueId::generate(1024*512));
//
echo "\n\n";
var_dump($data);
echo "\n\n";
// echo "\n\n >> $data2 << \n\n";
//

// $data = $client->send('wstatus', "");
// echo "\n\n".var_export($data, true)."\n\n";
//
// $data = $client->send('pause', "");
//
// echo "\n\n".var_export($data, true)."\n\n";
//
// $data = $client->send('wstatus', "");
//
// echo "\n\n".var_export($data, true)."\n\n";
//

// $data = $client->send('block', "");

// for ($i=0; $i < 10000; $i++) {
//     $data = $client->send('wstatus', "");
//     echo "\n\n".var_export($data, true)."\n\n";
// }

// $data = $client->send('foo', "");

// $client->close();
