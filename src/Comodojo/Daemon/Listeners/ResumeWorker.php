<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class ResumeWorker extends AbstractListener {

    public function handle(EventInterface $event) {

        $loop = $event->getLoop();

        $loop->resume();

    }

}
