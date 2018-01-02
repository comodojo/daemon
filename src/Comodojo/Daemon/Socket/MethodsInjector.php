<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Daemon\Daemon as AbstractDaemon;
use \Comodojo\RpcServer\RpcServer;
use \Comodojo\RpcServer\RpcMethod;

class MethodsInjector {

    public static function inject(RpcServer $server, AbstractDaemon $daemon) {

        $mmanager = $server->methods();

        $mmanager->add(
            RpcMethod::create("daemon.shutdown", function($params, $daemon) {
                $daemon->stop();
                return 0;
            }, $daemon)
            ->setDescription("Stop daemon closing workers")
            ->setReturnType('int')
        );

        $mmanager->add(
            RpcMethod::create("worker.list", function($params, $daemon) {
                $workers = $daemon->getWorkers()->get();
                $wlist = [];
                foreach ($workers as $worker => $opts) {
                    $wlist[] = [
                        'name' => $worker,
                        'looptime' => $opts->getLoopTime(),
                        'forever' => $opts->getForever()
                    ];
                }
                return $wlist;
            }, $daemon)
            ->setDescription("List installed workers")
            ->setReturnType('array')
        );

        $mmanager->add(
            RpcMethod::create("worker.status", function($params, $daemon) {
                $name = $params->get('name');
                return $daemon->getWorkers()->status($name);
            }, $daemon)
            ->setDescription("Get info about installed worker(s)")
            ->setReturnType('array')
            ->addSignature()
            ->addParameter('string','name')
            ->setReturnType('int')
        );

        $mmanager->add(
            RpcMethod::create("worker.pause", function($params, $daemon) {
                $name = $params->get('name');
                return $daemon->getWorkers()->pause($name);
            }, $daemon)
            ->setDescription("Pause installed worker(s)")
            ->setReturnType('array')
            ->addSignature()
            ->addParameter('string','name')
            ->setReturnType('boolean')
        );

        $mmanager->add(
            RpcMethod::create("worker.resume", function($params, $daemon) {
                $name = $params->get('name');
                return $daemon->getWorkers()->resume($name);
            }, $daemon)
            ->setDescription("Resume installed worker(s)")
            ->setReturnType('array')
            ->addSignature()
            ->addParameter('string','name')
            ->setReturnType('boolean')
        );

    }

}
