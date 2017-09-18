<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Foundation\Validation\DataFilter;

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

abstract class AbstractSocket {

    const VERSION = '1.0';

    const DEFAULT_READ_BUFFER = 1024;

    const DEFAULT_SOCKET_PORT = 10042;

    protected $socket;

    protected $handler;

    protected $socket_domain;

    protected $socket_type = SOCK_STREAM;

    protected $socket_protocol = 0;

    protected $socket_resource;

    protected $socket_port = 0;

    protected $read_buffer;

    public function __construct($handler, $read_buffer = null) {

        $this->setHandler($handler);

        $this->read_buffer = is_null($read_buffer)
            ? self::DEFAULT_READ_BUFFER
            : DataFilter::filterInteger($read_buffer, 1024, PHP_INT_MAX, self::DEFAULT_READ_BUFFER);

    }

    public function getSocket() {

        return $this->socket;

    }

    abstract public function connect();

    abstract public function close();

    protected function setSocket($socket) {

        $this->socket = $socket;

        return $this;

    }

    protected function setHandler($handler) {

        $this->handler = $handler;

        list($domain, $resource) = preg_split( '@(:\/\/)@', $handler );

        $domain = strtolower($domain);

        if ( $domain == 'unix' ) {
            $this->socket_domain =  AF_UNIX;
            $this->socket_resource = $resource;
        } else {
            $this->socket_domain = AF_INET;
            $this->socket_protocol = $domain == 'udp' ? SOL_UDP : SOL_TCP;
            if (strpos($resource, ':') !== false) {
                list($this->socket_resource, $this->socket_port) = explode(":", $resource);
            } else {
                $this->socket_resource = $resource;
                $this->socket_port = self::DEFAULT_SOCKET_PORT;
            }

        }

    }

    public static function getSocketError($socket = null) {

        return socket_strerror(socket_last_error($socket));

    }

}
