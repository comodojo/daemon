<?php namespace Comodojo\Daemon\Tests\Mock;

use \Comodojo\Daemon\Daemon as AbstractDaemon;
use \Comodojo\RpcServer\RpcServer;
use \Comodojo\RpcServer\RpcMethod;

class MethodsInjector {

    public static function inject(AbstractDaemon $daemon) {

        $mmanager = $daemon->getSocket()->getRpcServer()->methods();

        $mmanager->add(
            RpcMethod::create("test.echo", function($params, $daemon) {
                $message = $params->get('message');
                return $message;
            }, $daemon)
            ->setDescription("I'm here to reply your data")
            ->addParameter('string','message')
            ->setReturnType('string')
        );

        // $mmanager->add(
        //     RpcMethod::create("daemon.shutdown", function($params, $daemon) {
        //         $daemon->stop();
        //         return 0;
        //     }, $daemon)
        //     ->setDescription("Stop daemon closig workers")
        //     ->setReturnType('int')
        // );

    }

}
