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

    const DEFAULT_READ_BUFFER = 4096;

    protected $socket;

    protected $handler;

    protected $read_buffer;

    public function __construct($handler, $read_buffer = null) {

        $this->handler = $handler;

        $this->read_buffer = is_null($read_buffer)
            ? self::DEFAULT_READ_BUFFER
            : DataFilter::filterInteger($read_buffer, 1024, PHP_INT_MAX, self::DEFAULT_READ_BUFFER);

    }

    public function getSocket() {

        return $this->socket;

    }

    protected function setSocket($socket) {

        $this->socket = $socket;

        return $this;

    }

    abstract public function connect();

    abstract public function close();

}
