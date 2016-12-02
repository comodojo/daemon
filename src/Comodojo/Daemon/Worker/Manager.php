<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Daemon;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\DataAccess\IteratorTrait;
use \Comodojo\Foundation\DataAccess\CountableTrait;
use \Psr\Log\LoggerInterface;
use \Iterator;
use \Countable;

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

 class Manager implements Iterator, Countable {

    use IteratorTrait;
    use CountableTrait;

    public $logger;

    public $events;

    private $data = [];

    private $daemon;

    public function __construct(LoggerInterface $logger, EventsManager $events, Daemon $daemon) {

        $this->logger = $logger;

        $this->events = $events;

        $this->daemon = $daemon;

    }

    public function install(WorkerInterface $worker, $looptime = 1, $forever = false) {

        $name = $worker->getName();

        if ( $this->installed($name) ) {
            throw new Exception("Worker already installed");
        }

        $w = new Worker();
        $w->instance = $worker;
        $w->looptime = $looptime;
        $w->forever = $forever;
        $w->output = new SharedMemory((int)'1'.hexdec($worker->getId()));
        $w->input = new SharedMemory((int)'2'.hexdec($worker->getId()));

        $this->data[$name] = $w;

        return $this;

    }

    public function setPid($name, $pid) {

        if ( !$this->installed($name) ) {
            throw new Exception("Worker not installed");
        }

        $this->data[$name]->pid = $pid;

        return $this;

    }

    public function get($name = null) {

        if ( is_null($name) ) return $this->data;

        if ( !$this->installed($name) ) {
            throw new Exception("Worker not installed");
        }

        return $this->data[$name];

    }

    public function installed($name) {

        return array_key_exists($name, $this->data);

    }

    public function start($name, $unmask = false) {

        // fork worker
        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error("Could not create worker $name (fork error)");
            $daemon->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Worker $name created with pid $pid");
            $this->setPid($name, $pid);
            return;
        }

        // get daemon and worker
        $worker = $this->get($name);
        $daemon = $this->daemon;

        // update pid reference
        $daemon->pid = ProcessTools::getPid();

        // cleanup events
        $daemon->signals->any()->default();

        // unmask signals (if restart)
        if ( $unmask === true ) {
            $daemon->signals->any()->unmask();
        }

        // inject events and logger
        $logger = $daemon->logger->withName($name);
        $events = new EventsManager($logger);
        $worker->instance->logger = $logger;
        $worker->instance->events = $events;

        $loop = new Loop($worker);

        $this->declassDaemon($daemon);

        $loop->start();

        $daemon->end(0);

    }

    public function stop($name = null) {

        foreach ($this->data as $wname => $worker) {

            if ( is_null($name) || $name == $wname ) {

                // fix the wait time;
                $time = time() + 5;

                // try to gently ask the worker to close
                $worker->output->send('stop');

                while (time() < $time) {

                    if ( !$this->running($worker->pid) ) break;
                    usleep(20000);

                }

                // close the shared memory block
                $worker->input->close();
                $worker->output->close();

                // terminate the worker if still alive
                if ($this->running($worker->pid)) ProcessTools::term($worker->pid, 5, SIGTERM);

            }

        }

    }

    public function running($pid) {

        return ProcessTools::isRunning($pid);

    }

    public function status($name = null) {

        if ( $name === null ) {

            $result = [];
            foreach ($this->data as $name => $worker) {
                $result[$name] = $this->getStatus($worker);
            }
            return $result;

        }

        $worker = $this->get($name);

        return $this->getStatus($worker);

    }

    private function getStatus(Worker $worker) {

        return $worker->input->read();

    }

    private function declassDaemon($daemon) {

        // remove supervisor flag
        $daemon->supervisor = false;

        // remove supervisor flag
        $daemon->supervisor = false;

        // Unsubscribe supervisor default events (if any)
        $daemon->events->removeAllListeners('daemon.posix.'.SIGTERM);
        $daemon->events->removeAllListeners('daemon.posix.'.SIGINT);
        $daemon->events->removeAllListeners('daemon.socket.loop');

        // unset supervisor components
        unset($daemon->pidlock);
        unset($daemon->socket);
        unset($daemon->workers);
        unset($daemon->console);

    }

 }
