<?php namespace Comodojo\Daemon\Socket;

class Request extends AbstractMessage {

    protected $data = array(
        'command' => null,
        'payload' => array()
    );

}
