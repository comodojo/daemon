<?php namespace Comodojo\Daemon\Socket;

class Request extends AbstractMessage {

    protected $data = [
        'command' => null,
        'payload' => null
    ];

}
