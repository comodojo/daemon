<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Psr\Log\LoggerInterface;

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

 class Manager {

    public $logger;

    public $events;

    private $workers = [];

    public function __construct(LoggerInterface $logger, EventsManager $events) {

        $this->logger = $logger;

        $this->events = $events;

    }

    public function install(WorkerInterface $worker, $looptime = 1) {

        $name = $worker->getName();

        if ( $this->installed($name) ) {
            throw new Exception("Worker already installed");
        }

        $w = new Worker();
        $w->instance = $worker;
        $w->looptime = $looptime;

        $this->workers[$name] = $w;

        return $this;

    }

    public function setPid($name, $pid) {

        if ( !$this->installed($name) ) {
            throw new Exception("Worker not installed");
        }

        $this->workers[$name]->pid = $pid;

        return $this;

    }

    public function get($name = null) {

        if ( is_null($name) ) return $this->workers;

        if ( !$this->installed($name) ) {
            throw new Exception("Worker not installed");
        }

        return $this->workers[$name];

    }

    public function installed($name) {

        return array_key_exists($name, $this->workers);

    }

    public function stop($pid = null) {

        if ( empty($pid) ) {

            foreach ($this->workers as $worker) {

                // posix_kill($worker->pid, SIGTERM);
                ProcessTools::term($worker->pid, 5);

            }

        } else {


        }

    }

 }
