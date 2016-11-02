<?php namespace Comodojo\Daemon\Socket;

abstract class AbstractSocket {

    const VERSION = '1.0';

    protected $socket;

    protected $handler;

    public function __construct($handler) {

        $this->handler = $handler;

    }

    public function socket() {
        return $this->socket;
    }

    abstract public function connect();

    abstract public function close();

}
