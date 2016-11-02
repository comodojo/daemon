<?php namespace Comodojo\Daemon\Socket;

class Commands {

    private $commands = array();

    public function add($command, callable $callable) {

        $this->commands[$command] = $callable;

        return $this;

    }

    public function get($command) {

        if ( $this->has($command) ) {
            return $this->commands[$command];
        }

        return null;

    }

    public function delete($command) {

        if ( $this->has($command) ) {
            unset($this->commands[$command]);
            return true;
        }

        return false;

    }

    public function has($command) {

        return array_key_exists($command, $this->commands);

    }

    public function list() {

        return array_keys($this->commands);

    }

}
