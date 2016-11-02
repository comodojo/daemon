<?php namespace Comodojo\Daemon\Socket;

use \Psr\Log\LoggerInterface;

class Server extends AbstractSocket {

    private $active;

    private $commands;

    private $logger;

    public function __construct($handler, LoggerInterface $logger) {

        parent::__construct($handler);

        $this->logger = $logger;

        $this->commands = new Commands();

        $this->active = true;

    }

    public function commands() {

        return $this->commands;

    }

    public function logger() {

        return $this->logger;

    }

    public function connect() {

        $this->socket = stream_socket_server($this->handler, $errno, $errorMessage);

        return $this;

    }

    public function close() {

        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

        unlink($this->handler);

    }

    public static function create($handler, LoggerInterface $logger) {

        $socket = new Server($handler, $logger);

        $socket->connect();

        return $socket;

    }

    public function listen() {

        stream_set_blocking($this->socket, 0);

        $this->logger->info("Socket listening");

        $this->loop();

        $this->close();

    }

    public function stop() {

        $this->active = false;

    }

    public function loop() {

        $clients = [];

        while($this->active) {

            $sockets = $clients;
            $sockets[] = $this->socket;

            if( stream_select($sockets, $write, $except, 200000 ) === false ) {
                throw new Exception("Error selecting sockets");
            }

            // Accept new connections (if any)
            if( in_array($this->socket, $sockets) ) {

                $client = stream_socket_accept($this->socket);

                if ($client) {

                    $clients[] = $client;

                    $message = new Greeter();

                    $message->status = 'connected';

                    $this->write($client, $message);

                }

                unset($sockets[array_search($this->socket, $sockets)]);

            }

            // Serve active clients
            foreach($sockets as $socket) {

                $message = $this->read($socket);

                if ( $message === null ) {

                    unset($clients[ array_search($socket, $clients) ]);
                    $this->hangup($socket);
                    continue;

                }

                if ( $message === false ) continue;

                $output = $this->serve($message);

                $this->write($socket, $output);

            }

        }

    }

    private function write($client, AbstractMessage $message) {

        $datagram = $message->serialize();

        $this->logger->debug("Sending message", $message->export());

        stream_socket_sendto($client, $datagram);

    }

    private function read($client) {

        $datagram = stream_socket_recvfrom($client, 4096);

        if ('' !== $datagram && false !== $datagram) {

            $message = new Request();

            $message->unserialize(trim($datagram));

            $this->logger->debug("Received message", $message->export());

            return $message;

        }

        if ('' === $datagram || false === $datagram || !is_resource($client) || feof($client)) {
            return null;
        }

        return false;

    }

    private function serve(Request $request) {

        $response = new Response();

        if ( $this->commands->has($request->command) ) {

            $callable = $this->commands->get($request->command);

            try {

                $response->message = call_user_func($callable, $request->payload);

                $response->status = true;

            } catch (Exception $e) {

                $response->status = false;

                $response->message = $e->getMessage();

            }

            return $response;

        }

        $response->status = false;

        $response->message = "Unknown command";

        return $response;

    }

    private function hangup($client) {

        stream_socket_shutdown($client, STREAM_SHUT_RDWR);
        stream_set_blocking($client, false);
        fclose($client);

    }

}
