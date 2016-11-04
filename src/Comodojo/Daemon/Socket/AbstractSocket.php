<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Foundation\Validation\DataFilter;

abstract class AbstractSocket {

    const VERSION = '1.0';

    const DEFAULT_READ_BUFFER = 4096;

    protected $socket;

    protected $handler;

    protected $read_buffer;

    public function __construct($handler, $read_buffer = null) {

        $this->handler = $handler;

        $this->read_buffer = is_null($read_buffer)
            ? self::DEFAULT_READ_BUFFER
            : DataFilter::filterInteger($read_buffer, 1024, PHP_INT_MAX, self::DEFAULT_READ_BUFFER);

    }

    public function socket() {
        return $this->socket;
    }

    abstract public function connect();

    abstract public function close();

}
