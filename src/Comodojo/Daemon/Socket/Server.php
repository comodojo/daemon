<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Daemon\Process;
use \Comodojo\Daemon\Events\SocketEvent;
use \Comodojo\Foundation\Events\EventsTrait;
use \Comodojo\Foundation\Logging\LoggerTrait;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\Validation\DataFilter;
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

    const DEFAULT_TIMEOUT = 10;

    private $active;

    private $process;

    private $timeout;

    protected $commands;

    public function __construct(
        $handler,
        LoggerInterface $logger,
        EventsManager $events,
        Process $process,
        $read_buffer = null,
        $timeout = null
    ) {

        parent::__construct($handler, $read_buffer);

        $this->logger = $logger;
        $this->events = $events;
        $this->process = $process;

        $this->timeout = is_null($timeout)
            ? self::DEFAULT_TIMEOUT
            : DataFilter::filterInteger($timeout, 0, 600, self::DEFAULT_TIMEOUT);

        $this->commands = new Commands();

        $this->active = true;

    }

    public function getCommands() {

        return $this->commands;

    }

    public function connect() {

        $this->socket = @stream_socket_server($this->handler, $errno, $errorMessage);

        if ( $this->socket === false ) throw new SocketException("Socket unavailable");

        return $this;

    }

    public function close() {

        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

        $this->clean();

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

        $socket->connect();

        return $socket;

    }

    public function listen() {

        stream_set_blocking($this->socket, 0);

        $this->logger->info("Socket listening");

        $this->loop();

    }

    public function stop() {

        $this->active = false;

    }

    public function clean() {

        list($handler, $resource) = preg_split( '@(:\/\/)@', $this->handler );

        if ( $handler == 'unix' && file_exists($resource) ) unlink($resource);

    }

    protected function loop() {

        $clients = [];

        while($this->active) {

            $this->events->emit( new SocketEvent('loop', $this->process) );

            $sockets = $clients;
            $sockets[] = $this->socket;

            if( @stream_select($sockets, $write, $except, $this->timeout) === false ) {

                if ( $this->checkSocketError() && $this->active ) {
                    $this->logger->debug("Socket reset due to incoming signal");
                    pcntl_signal_dispatch();
                    continue;
                }

                $socket_error = error_get_last();
                throw new SocketException("Error selecting sockets: (".$socket_error['type'].") ".$socket_error['message']);

            }

            // Accept new connections (if any)
            if( in_array($this->socket, $sockets) ) {

                $client = stream_socket_accept($this->socket);

                if ($client) {

                    $clients[] = $client;

                    $this->open($client);

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

        $datagram = stream_socket_recvfrom($client, $this->read_buffer);

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

                $response->message = call_user_func($callable, $request->payload, $this->process);

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

    private function open($client) {

        $this->events->emit( new SocketEvent('client.connect', $this->process) );

        $message = new Greeter();

        $message->status = 'connected';

        $this->write($client, $message);

    }

    private function hangup($client) {

        stream_socket_shutdown($client, STREAM_SHUT_RDWR);
        stream_set_blocking($client, false);
        fclose($client);

        $this->events->emit( new SocketEvent('client.hangup', $this->process) );

    }

    private function checkSocketError() {

        // this method is taken as-is from symphony ProcessPipes
        $lastError = error_get_last();
        // stream_select returns false when the `select` system call is interrupted by an incoming signal
        return isset($lastError['message']) && false !== stripos($lastError['message'], 'interrupted system call');

    }

}
