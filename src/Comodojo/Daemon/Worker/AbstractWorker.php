<?php namespace Comodojo\Daemon\Worker;

use \Psr\Log\LoggerInterface;
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

abstract class AbstractWorker implements WorkerInterface {

    private $name;

    private $id;

    public $logger;

    public $events;

    public $signals;

    public function __construct() {

        $this->id = uniqid();

        $this->name = is_null($this->name) ? 'worker.'.$this->id : $this->name;

    }

    public function getName() {

        return $this->name;

    }

    public function getId() {

        return $this->id;

    }

    abstract public function spinup();

    abstract public function loop();

    abstract public function spindown();

}
