<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Exception\SocketException;

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

class Connection {

    protected $socket;

    protected $index;

    public function __construct($socket, $i) {

        $this->index = $i;
        $this->socket = @socket_accept($socket);

        if ( $this->socket === false ) {
            $error = self::checkConnectionError($socket);
            if ( $error !== true ) {
                throw new SocketException("Cannot put socket in listening mode: $error");
            }
        }

    }

    public function getIndex() {
        return $this->index;
    }

    public function getSocket() {
        return $this->socket;
    }

    public function destroy() {
        socket_close($this->socket);
    }

    protected static function checkConnectionError($socket) {

        $err_code = socket_last_error($socket);
        $err_string = socket_strerror($err_code);

        if ( $err_code === 11 || strtolower($err_string) == 'success' ) return true;

        return $err_string;

    }

}
