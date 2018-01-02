<?php namespace Comodojo\Daemon\Tests\Mock;

use \Comodojo\Daemon\Daemon as AbstractDaemon;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Psr\Log\LoggerInterface;

class Daemon extends AbstractDaemon {

    public function setup() {

        MethodsInjector::inject($this);

        // $this->getSocket()->getCommands()->add('close', function ($daemon, $data) {
        //     $daemon->stop();
        //     return "Closing daemon";
        // });
        //
        // $this->getSocket()->getCommands()->add('wstatus', function ($daemon, $data) {
        //     $status = $daemon->getWorkers()->status();
        //     return $status;
        // });
        //
        // $this->getSocket()->getCommands()->add('block', function ($daemon, $data) {
        //     sleep(5);
        //     return true;
        // });
        //
        // $this->getSocket()->getCommands()->add('pause', function ($daemon, $data) {
        //     $daemon->getWorkers()->get('target_worker')->getOutputChannel()->send('pause');
        //     return 1;
        // });

    }

}
