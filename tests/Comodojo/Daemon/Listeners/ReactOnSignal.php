<?php namespace Comodojo\Daemon\Tests\Mock\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class ReactOnSignal extends AbstractListener {

    protected $last_signal;

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $signal = $event->getName();

        $daemon->getLogger()->info("Hey, someone sends me a signal: ".$signal);
        // var_dump($signal);
        $this->last_signal = $signal;

    }

    public function getLastSignal() {

        return $this->last_signal;

    }

}
