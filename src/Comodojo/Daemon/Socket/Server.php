<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Daemon\Process;
use \Comodojo\Daemon\Events\SocketEvent;
use \Comodojo\Foundation\Events\EventsTrait;
use \Comodojo\Foundation\Logging\LoggerTrait;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\Validation\DataFilter;
use \Comodojo\RpcServer\RpcServer;
use \Psr\Log\LoggerInterface;
use \Comodojo\Exception\SocketException;
use \Exception;

/**
 * @package     Comodojo Daemon
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     MIT
 *
 * LICENSE:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Server extends AbstractSocket {

    use EventsTrait;
    use LoggerTrait;

    /*
     * Socket default timeout
     * @var int
     */
    const DEFAULT_TIMEOUT = 10;

    /*
     * Socket default max client
     * @var int
     */
    const DEFAULT_MAX_CLIENTS = 10;

    private $active = false;

    private $process;

    private $timeout;

    private $connections = [];

    protected $rpc_server;

    protected $max_connections;

    /*
     * Class constructor
     *
     * @param int
     */
    public function __construct(
        $handler,
        LoggerInterface $logger,
        EventsManager $events,
        Process $process,
        $read_buffer = null,
        $timeout = null,
        $max_connections = null
    ) {

        parent::__construct($handler, $read_buffer);

        $this->logger = $logger;
        $this->events = $events;
        $this->process = $process;

        $this->timeout = is_null($timeout)
            ? self::DEFAULT_TIMEOUT
            : DataFilter::filterInteger($timeout, 0, 600, self::DEFAULT_TIMEOUT);

        $this->max_connections = is_null($max_connections)
            ? self::DEFAULT_MAX_CLIENTS
            : DataFilter::filterInteger($max_connections, 1, 1024, self::DEFAULT_MAX_CLIENTS);

        $this->rpc_server = new RpcServer(RpcServer::XMLRPC, $logger);

        MethodsInjector::inject($this->rpc_server, $process);

    }

    public static function create(
        $handler,
        LoggerInterface $logger,
        EventsManager $events,
        Process $process,
        $read_buffer = null,
        $timeout = null
    ) {

        $socket = new Server($handler, $logger, $events, $process, $read_buffer, $timeout);

        return $socket->connect();

    }

    public function getRpcServer() {

        return $this->rpc_server;

    }

    public function connect() {

        $this->socket = @socket_create(
            $this->socket_domain,
            $this->socket_type,
            $this->socket_protocol
        );

        if ( $this->socket === false ) {
            $error = self::getSocketError();
            throw new SocketException("Socket unavailable: $error");
        }

        $bind = @socket_bind(
            $this->socket,
            $this->socket_resource,
            $this->socket_port
        );

        if ( $bind === false ) {
            $error = self::getSocketError($this->socket);
            throw new SocketException("Cannot bind socket: $error");
        }

        socket_set_nonblock($this->socket);

        return $this;

    }

    public function close() {

        $this->stop();

        @socket_shutdown($this->socket, 2);

        $this->clean();

    }

    public function listen() {

        $listen = socket_listen($this->socket);

        if ( $listen === false ) {
            $error = self::getSocketError($this->socket);
            throw new SocketException("Cannot put socket in listening mode: $error");
        }

        $this->logger->debug("Socket listening on ".$this->handler);

        $this->active = true;

        try {

            do {
                $this->loop();
            } while ($this->active);

        } catch (Exception $e) {

            $this->close();

            throw $e;

        }

    }

    public function stop() {

        $this->active = false;

    }

    public function clean() {

        if ( $this->socket_domain == AF_UNIX && file_exists($this->socket_resource) ) {
            unlink($this->socket_resource);
        }

    }

    protected function loop() {

        $sockets[0] = $this->socket;

        $sockets = array_merge($sockets, array_map(function($connection) {
            return $connection->getSocket();
        }, $this->connections));

        $select = @socket_select($sockets, $write, $except, $this->timeout);

        if ($select === false) {

            if ( $this->checkSocketError() && $this->active ) {
                $this->logger->debug("Socket reset due to incoming signal");
                pcntl_signal_dispatch();
                return;
            }

            $socket_error_message = self::getSocketError($this->socket);

            throw new SocketException("Error selecting socket: $socket_error_message");

        }

        if( $select < 1 ) {
            return;
        }

        if( in_array($this->socket, $sockets) ) {

            for ($i=0; $i < $select; $i++) {

                if ( empty($this->connections[$i]) ) {

                    try {

                        $this->logger->debug("New incoming connection ($i)");

                        $this->connections[$i] = new Connection($this->socket, $i);

                        $this->open($this->connections[$i], 'connected');

                    } catch (SocketException $se) {

                        $this->logger->warning("Error accepting client: ".$se->getMessage());

                    }

                    unset($sockets[$i]);

                }

            }

        }

        for ($i = 0; $i < $this->max_connections; $i++) {

            if (isset($this->connections[$i])) {

                $client = $this->connections[$i];

                if (in_array($client->getSocket(), $sockets)) {

                    $message = $this->read($client);

                    if ($message === null) {
                    // if ($message == null) {
                         $this->hangup($client);
                    } else if ( $message === false ) {
                         continue;
                    } else {
                        $output = $this->serve($message);
                        $this->write($client, $output);
                    }

                }

            }

        }

    }

    private function write(Connection $connection, AbstractMessage $message) {

        $socket = $connection->getSocket();
        $datagram = $message->serialize()."\r\n";

        return @socket_write($socket, $datagram, strlen($datagram));

    }

    private function read(Connection $connection) {

        $datagram = '';
        $socket = $connection->getSocket();

        while (true) {
            $recv = @socket_read($socket, $this->read_buffer, PHP_NORMAL_READ);
            if ( $recv === false ) return null;
            $datagram .= $recv;
            if (empty($recv) || strstr($recv, PHP_EOL)) break;
        }

        $datagram = trim($datagram);

        if ( !empty($datagram) && $datagram !== false) {

            $message = new Request();

            $message->unserialize($datagram);

            return $message;

        }

        // the null return is because of socket_read strange behaviour in
        // darwin/bsd OS. It will never match the PHP_EOL keeping socket channel
        // open indefinitely.
        return null;

    }

    private function serve(Request $request) {

        $response = new Response();

        if ( $request->content_type == 'application/json') {
            $this->rpc_server->setProtocol(RpcServer::JSONRPC);
        }

        try {

            $response->message = $this->rpc_server
                ->setPayload($request->message)
                ->serve();

            $response->status = true;

        } catch (Exception $e) {

            $response->message = $e->getMessage();
            $response->status = false;

        }

        return $response;

    }

    private function open(Connection $client, $status) {

        $idx = $client->getIndex();

        $this->logger->debug("Opening connection ($idx), sending greeter");

        $this->events->emit( new SocketEvent('client.connect', $this->process) );

        $message = new Greeter();

        $message->status = $status;

        $this->write($client, $message);

    }

    private function hangup(Connection $connection) {

        $index = $connection->getIndex();

        $this->logger->debug("Client hangup ($index)");

        $this->connections[$index]->destroy();
        unset($this->connections[$index]);

        $this->events->emit( new SocketEvent('client.hangup', $this->process) );

    }

    private function checkSocketError() {

        // this method is taken as-is from symphony ProcessPipes
        $lastError = error_get_last();
        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return isset($lastError['message']) && false !== stripos($lastError['message'], 'interrupted system call');

    }

}
