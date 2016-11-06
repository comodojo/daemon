<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class StopWorker extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $daemon->loopactive = false;

    }

}
