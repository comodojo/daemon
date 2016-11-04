<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Psr\Log\LoggerInterface;

abstract class AbstractWorker {

    private $pid;

    public $logger;

    public $events;

    public function __construct($pid, LoggerInterface $logger, EventsManager $events) {

        $this->pid = $pid;

        $this->logger = $logger;

        $this->events = $events;

    }

    abstract public function spinup();

    abstract public function loop($time);

}
