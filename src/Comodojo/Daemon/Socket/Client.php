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

        $this->socket = @socket_create(
            $this->socket_domain,
            $this->socket_type,
            $this->socket_protocol
        );

        if ( $this->socket === false ) {
            $error = self::getSocketError();
            throw new SocketException("Socket unavailable: $error");
        }

        $connect = @socket_connect(
            $this->socket,
            $this->socket_resource,
            $this->socket_port
        );

        if ( $connect === false ) {
            $error = self::getSocketError($this->socket);
            throw new SocketException("Cannot connect to socket: $error");
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

        return socket_close($this->socket);

    }

    public static function create($handler, $read_buffer = null) {

        $client = new Client($handler, $read_buffer);

        return $client->connect();

    }

    public function send($command, $payload = null) {

        $sent = $this->write($command, $payload);

        // TODO: manage exceptions!

        $received = $this->read();

        if ( $received->status === false ) {
            throw new Exception($received->message);
        }

        return $received->message;

    }

    protected function write($command, $payload = null) {

        $request = new Request();

        $request->command = $command;
        $request->payload = $payload;

        $datagram = $request->serialize()."\r\n";

        return socket_write($this->socket, $datagram, strlen($datagram));

    }

    protected function read() {

        $response = new Response();

        $datagram = $this->rawRead();

        if ( is_null($datagram) ) {
            $response->status = false;
            $response->message = "Server has gone away";
        } else if ( empty($datagram) ) {
            $response->status = false;
            $response->message = "No response received";
        } else {
            $response->unserialize($datagram);
        }

        return $response;

    }

    protected function readGreeter() {

        $greeter = new Greeter();

        $datagram = $this->rawRead();

        if ( is_null($datagram) ) {
            $greeter->status = 'greeter not received';
            return $greeter;
        }

        if ( $datagram === false ) {
            $greeter->status = 'server has gone away';
            return $greeter;
        }

        return $greeter->unserialize($datagram);

    }

    protected function rawRead() {

        $datagram = '';

        while (true) {
            $recv = @socket_read($this->socket, $this->read_buffer, PHP_NORMAL_READ);
            if ( $recv === false ) break;
            if ( $recv === 0 ) return null;
            $datagram .= $recv;
            if(strstr($recv, PHP_EOL)) break;
        }

        return trim($datagram);

    }


}
