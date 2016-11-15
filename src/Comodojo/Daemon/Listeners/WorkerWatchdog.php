<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class WorkerWatchdog extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $workers = $daemon->workers;

        foreach ($workers as $name => $worker) {

            if ($workers->running($worker->pid)) {
                $daemon->logger->debug("Worker $name seems to be running");
            } else {
                $daemon->logger->debug("Worker $name has exited");
                if ($worker->forever) {
                    $daemon->logger->debug("Attempting to restart $name");
                    $workers->start($name);
                    $daemon->logger->debug("$name restarted");
                }
            }

        }

    }

}
