<?php namespace Comodojo\Daemon\Tests\Socket;

use \Comodojo\Daemon\Tests\Base\AbstractIndirectTestCase;
use \Comodojo\RpcClient\RpcRequest;
use \Comodojo\Foundation\Utils\UniqueId;

class IndirectSocketTest extends AbstractIndirectTestCase {

    public function testShortEcho() {

        $message = 'This is a spam message, please reply to marvin@magrathea.uni.ver.se';
        $request = RpcRequest::create("test.echo", [$message]);
        $data = $this->send($request);
        $this->assertEquals($message, $data);

    }

    public function testVeryLongEcho() {

        $message = UniqueId::generate(1024*512);
        $request = RpcRequest::create("test.echo", [$message]);
        $data = $this->send($request);
        $this->assertEquals($message, $data);

    }

    public function testDefaultMethods() {

        $default_methods = [
            "system.getCapabilities",
            "system.listMethods",
            "system.methodHelp",
            "system.methodSignature",
            "system.multicall"
        ];

        $request = RpcRequest::create("system.listMethods");
        $data = $this->send($request);

        foreach ($default_methods as $method) {
            $this->assertContains($method, $data);
        }

    }

    public function testMultipleRequests() {

        $message_one = "Marvin";
        $message_two = "Ford";

        $request_one = RpcRequest::create("test.echo", [$message_one]);
        $request_two = RpcRequest::create("test.echo", [$message_two]);

        $data = $this->bulkSend($request_one, $request_two);

        $this->assertEquals($message_one, $data[0]);
        $this->assertEquals($message_two, $data[1]);

    }

    // public function testShutdown() {
    //
    //     $request = RpcRequest::create("daemon.shutdown");
    //
    //     $data = $this->send($request);
    //
    //     var_dump($data);
    //
    // }

    public function testWorkerMethods() {

        $request = RpcRequest::create("worker.list");
        $data = $this->send($request);
        $this->assertCount(3, $data);

        $request = RpcRequest::create("worker.status");
        $data = $this->send($request);
        foreach ($data as $status) {
            $this->assertEquals(1, $status);
        }

        $request = RpcRequest::create("worker.pause", ['worker_one']);
        $data = $this->send($request);
        $this->assertTrue($data);

        sleep(2);

        $request = RpcRequest::create("worker.status", ['worker_one']);
        $data = $this->send($request);
        $this->assertEquals(2, $data);

        $request = RpcRequest::create("worker.resume", ['worker_one']);
        $data = $this->send($request);
        $this->assertTrue($data);

        sleep(2);

        $request = RpcRequest::create("worker.status", ['worker_one']);
        $data = $this->send($request);
        $this->assertEquals(1, $data);

    }

}
