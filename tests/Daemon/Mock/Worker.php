<?php namespace Comodojo\Daemon\Tests\Mock;

use \Comodojo\Daemon\Worker\AbstractWorker;

class Worker extends AbstractWorker {

    public function spinup() {

        $this->logger->info("Worker ".$this->getName()." spinning up...");

        /// $this->events->subscribe('daemon.posix.'.SIGUSR1, '\Comodojo\Daemon\Tests\Mock\Listeners\ReactOnSignal');

    }

    public function loop() {

        $this->logger->info("Worker ".$this->getName()." looping");

    }

    public function spindown() {

        $this->logger->info("Worker ".$this->getName()." spinning down.");

    }

}
