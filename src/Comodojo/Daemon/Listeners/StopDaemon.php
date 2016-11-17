<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class StopDaemon extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $daemon->logger->info('TERM or INT signal received, stopping daemon gracefully');

        $daemon->stop();

    }

}
