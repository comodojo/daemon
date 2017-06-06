<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Daemon;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Daemon\Traits\EventsTrait;
use \Comodojo\Daemon\Traits\LoggerTrait;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\DataAccess\IteratorTrait;
use \Comodojo\Foundation\DataAccess\CountableTrait;
use \Psr\Log\LoggerInterface;
use \Iterator;
use \Countable;
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

 class Manager implements Iterator, Countable {

    use IteratorTrait;
    use CountableTrait;
    use EventsTrait;
    use LoggerTrait;

    private $data = [];

    private $daemon;

    public function __construct(LoggerInterface $logger, EventsManager $events, Daemon $daemon) {

        $this->logger = $logger;

        $this->events = $events;

        $this->daemon = $daemon;

    }

    /**
     * Install a worker into the stack
     *
     * @param WorkerInterface $worker
     * @param int $looptime
     * @param bool $forever
     * @return Manager
     */
    public function install(WorkerInterface $worker, $looptime = 1, $forever = false) {

        $name = $worker->getName();

        if ( $this->isInstalled($name) ) {
            throw new Exception("Worker already installed");
        }

        $w = Worker::create()
            ->setInstance($worker)
            ->setLooptime($looptime)
            ->setForever($forever)
            ->setInputChannel(new SharedMemory((int)'1'.hexdec($worker->getId())))
            ->setOutputChannel(new SharedMemory((int)'2'.hexdec($worker->getId())));

        $this->data[$name] = $w;

        return $this;

    }

    public function setPid($name, $pid) {

        if ( !$this->isInstalled($name) ) {
            throw new Exception("Worker not installed");
        }

        $this->data[$name]->setPid($pid);

        return $this;

    }

    public function get($name = null) {

        if ( is_null($name) ) return $this->data;

        if ( !$this->isInstalled($name) ) {
            throw new Exception("Worker not installed");
        }

        return $this->data[$name];

    }

    public function isInstalled($name) {

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
        $daemon->setPid(ProcessTools::getPid());

        // cleanup events
        $daemon->getSignals()->any()->setDefault();

        // unmask signals (if restart)
        if ( $unmask === true ) {
            $daemon->getSignals()->any()->unmask();
        }

        // inject events and logger
        $logger = $daemon->getLogger()->withName($name);
        $events = new EventsManager($logger);
        $worker->getInstance()->setLogger($logger);
        $worker->getInstance()->setEvents($events);

        $loop = new Loop($worker);

        $daemon->declass();

        $loop->start();

        $daemon->end(0);

    }

    public function stop($name = null) {

        foreach ($this->data as $wname => $worker) {

            $wpid = $worker->getPid();

            if ( is_null($name) || $name == $wname ) {

                // fix the wait time;
                $time = time() + 5;

                // try to gently ask the worker to close
                $worker->getOutputChannel()->send('stop');

                while (time() < $time) {

                    if ( !$this->running($wpid) ) break;
                    usleep(20000);

                }

                // close the shared memory block
                $worker->getInputChannel()->close();
                $worker->getOutputChannel()->close();

                // terminate the worker if still alive
                if ($this->running($wpid)) ProcessTools::term($wpid, 5, SIGTERM);

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

        return $worker->getInputChannel()->read();

    }

 }
