<?php namespace Comodojo\Daemon\Tests\Mock\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class LoopStop extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        // if ( $daemon->loopcount > 5 ) $daemon->end(0);

    }

}
