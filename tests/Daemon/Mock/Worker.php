<?php namespace Comodojo\Daemon\Tests\Mock;

use \Comodojo\Daemon\Worker\AbstractWorker;

class Worker extends AbstractWorker {

    public function spinup() {

        $this->logger->info("Worker ".$this->getName()." spinning up...");

    }

    public function loop() {

        $this->logger->info("Worker ".$this->getName()." looping");

    }

    public function spindown() {

        $this->logger->info("Worker ".$this->getName()." spinning down.");

    }

}
