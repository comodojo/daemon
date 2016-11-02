<?php namespace Comodojo\Daemon\Socket;

class Greeter extends AbstractMessage {

    protected $data = array(
        'status' => null,
        'version' => AbstractSocket::VERSION
    );

}
