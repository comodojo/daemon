<?php namespace Comodojo\Daemon\Socket;

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
 
class Commands {

    private $commands = array();

    public function add($command, callable $callable) {

        $this->commands[$command] = $callable;

        return $this;

    }

    public function get($command) {

        if ( $this->has($command) ) {
            return $this->commands[$command];
        }

        return null;

    }

    public function delete($command) {

        if ( $this->has($command) ) {
            unset($this->commands[$command]);
            return true;
        }

        return false;

    }

    public function has($command) {

        return array_key_exists($command, $this->commands);

    }

    public function getList() {

        return array_keys($this->commands);

    }

}
