<?php namespace Comodojo\Daemon\Tests\Base;

use \PHPUnit\Framework\TestCase;
use \Comodojo\Daemon\Socket\SocketTransport;
use \Comodojo\RpcClient\RpcClient;
use \Comodojo\RpcClient\RpcRequest;
use \Exception;

abstract class AbstractIndirectTestCase extends TestCase {

    const SOCKET_ADDR = 'tcp://127.0.0.1:10042';

    protected $client;

    public function setUp() {

        $transport = SocketTransport::create(self::SOCKET_ADDR);

        $this->client = new RpcClient(self::SOCKET_ADDR, null, $transport);

    }

    public function send(RpcRequest $request) {

        try {

            $this->client->addRequest($request);

            return $this->client->send();

        } catch (Exception $e) {

            throw $e;

        }

    }

    public function bulkSend(...$requests) {

        try {

            foreach ($requests as $request) {
                $this->client->addRequest($request);
            }

            return $this->client->send();

        } catch (Exception $e) {

            throw $e;

        }

    }

}
