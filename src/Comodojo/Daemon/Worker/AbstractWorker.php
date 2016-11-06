<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Foundation\Events\Manager as EventsManager;
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

    public $logger;

    public $events;

    public function __construct(LoggerInterface $logger, EventsManager $events) {

        $this->name = is_null($this->name) ? uniqid('daemon.worker.') : $this->name;

        $this->logger = $logger;

        $this->events = $events;

    }

    public function getName() {

        return $this->name;

    }

    abstract public function spinup();

    abstract public function loop();

    abstract public function spindown();

}
