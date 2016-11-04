<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Foundation\DataAccess\Model;

abstract class AbstractMessage extends Model {

    protected $mode = self::PROTECTDATA;

    public function __toString() {

        return var_export($this->data, true);

    }

    public function serialize() {

        return base64_encode(serialize($this->data));

    }

    public function unserialize($serialized) {

        $data = unserialize(base64_decode($serialized));

        return $this->merge($data);

    }

}
