<?php namespace Comodojo\Daemon\Tests\Mock\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class ReactOnSignal extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $daemon->logger->info("Hey, someone sends me a signal!!!");

    }

}
