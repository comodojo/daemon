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

        $this->getSocket()->getCommands()->add('echo', function($data, $daemon) {
            return $data;
        });

        $this->getSocket()->getCommands()->add('close', function ($data, $daemon) {
            $daemon->stop();
            return "Closing daemon";
        });

        $this->getSocket()->getCommands()->add('wstatus', function ($data, $daemon) {
            $status = $daemon->getWorkers()->status();
            return $status;
        });

        $this->getSocket()->getCommands()->add('block', function ($data, $daemon) {
            sleep(5);
            return true;
        });

        $this->getSocket()->getCommands()->add('pause', function ($data, $daemon) {
            $daemon->getWorkers()->get('target_worker')->getOutputChannel()->send('pause');
            return 1;
        });

    }

}
