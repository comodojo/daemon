<?php namespace Comodojo\Daemon\Socket;

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

class Client extends AbstractSocket {

    public function connect() {

        $this->socket = @stream_socket_client($this->handler, $errno, $errorMessage);

        if ( $this->socket === false ) {
            throw new SocketException("Socket connect failed: ($errno) $errorMessage");
        }

        $greeter = $this->readGreeter();

        if ( $greeter->status != 'connected' ) {
            throw new SocketException("Socket connect failed: ".$greeter->status);
        }

        if ( $greeter->version != self::VERSION ) {
            throw new SocketException("Socket connect failed: socket interface version mismatch");
        }

        return $this;

    }

    public function close() {

        return stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

    }

    public static function create($handler, $read_buffer = null) {

        $client = new Client($handler, $read_buffer);

        return $client->connect();

    }

    public function send($command, $payload = null) {

        $request = new Request();

        $request->command = $command;
        $request->payload = $payload;

        $sent = $this->write($request);

        // TODO: manage exceptions!

        $received = $this->readResponse();

        if ( $received->status === false ) {
            throw new Exception($received->message);
        }

        return $received->message;

    }

    protected function write(Request $request) {

        $datagram = $request->serialize();

        return stream_socket_sendto($this->socket, $datagram);

    }

    protected function read() {

        $datagram = stream_socket_recvfrom($this->socket, $this->read_buffer);

        return $datagram;

    }

    protected function readGreeter() {

        $greeter = new Greeter();

        $datagram = $this->read();

        if ( is_null($datagram) ) {
            $greeter->status = 'greeter not received';
            return $greeter;
        }

        return $greeter->unserialize($datagram);

    }

    protected function readResponse() {

        $response = new Response();

        $datagram = $this->read();

        if ( is_null($datagram) ) {
            $response->status = false;
            $response->message = "response not received";
            return $response;
        }

        return $response->unserialize($datagram);

    }

}
