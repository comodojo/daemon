<?php namespace Comodojo\Daemon\Tests\Mock;

// require __DIR__ . "/../../bootstrap.php";

use \Comodojo\Daemon\Daemon as AbstractDaemon;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Psr\Log\LoggerInterface;

class Daemon extends AbstractDaemon {

    // public function __construct($properties = [], LoggerInterface $logger = null, EventsManager $events = null) {
    //
    //     parent::__construct($properties, $logger, $events);
    //
    //     $this->start();
    //
    // }

    public function setup() {

        $this->socket->commands->add('echo', function($data, $daemon) {
            return $data;
        });

        $this->socket->commands->add('close', function ($data, $daemon) {
            $daemon->stop();
            return "Closing daemon";
        });

    }

}
