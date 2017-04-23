<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

class WorkerWatchdog extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $workers = $daemon->getWorkers();
        $logger = $daemon->getLogger();

        foreach ($workers as $name => $worker) {

            if ($workers->running($worker->pid)) {

                $logger->debug("Worker $name seems to be running");

            } else {

                $logger->debug("Worker $name has exited");

                if ($worker->forever) {
                    $logger->debug("Attempting to restart $name");
                    $workers->start($name, true);
                } else {
                    $logger->error("Worker $name has exited, shutting down daemon");
                    $daemon->stop();
                }

            }

        }

    }

}
