<?php namespace Comodojo\Daemon\Socket;

class Response extends AbstractMessage {

    protected $data = array(
        'status' => null,
        'message' => null
    );

}
