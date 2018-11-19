<?php namespace Comodojo\Daemon\Worker;

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

class SharedMemory {

    private $key;

    public function __construct($id) {

        $this->key = shmop_open($id, "c", 0644, 128);

    }

    public function getKey() {

        return $this->key;

    }

    public function send($signal) {

        return shmop_write($this->key, $signal, 0);

    }

    public function read() {

        return trim(shmop_read($this->key, 0, 128));

    }

    public function delete() {

        return shmop_write($this->key, str_pad('', 128), 0);

    }

    public function close() {

        shmop_delete($this->key);

        return shmop_close($this->key);

    }

}
