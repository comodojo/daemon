<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Daemon;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Daemon\Events\WorkerEvent;
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
        $w->shared = new SharedMemory(hexdec($worker->getId()));

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

    public function start($name) {

        // fork worker
        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error("Could not create worker $name (fork error)");
            $daemon->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Worker $name created with pid $pid");
            return $pid;
        }

        // declare ticks to handle few signals that will arrive at worker
        declare(ticks=5);

        $worker = $this->get($name);
        $daemon = $this->daemon;

        // remove supervisor flag
        $daemon->supervisor = false;

        // inject events and logger
        $daemon->logger = $daemon->logger->withName($name);
        // $daemon->events = new EventsManager($daemon->logger);
        $worker->instance->logger = $daemon->logger;
        // $worker->instance->events = $daemon->events;
        $worker->instance->events = new EventsManager($worker->instance->logger);

        // Unsubscribe supervisor default events (if any)
        $daemon->events->removeAllListeners('daemon.posix.'.SIGTERM);
        $daemon->events->removeAllListeners('daemon.posix.'.SIGINT);
        $daemon->events->removeAllListeners('daemon.socket.loop');

        // unset supervisor components
        unset($daemon->pidlock);
        unset($daemon->socket);
        unset($daemon->workers);
        unset($daemon->console);

        // set worker components
        $daemon->loopcount = 0;
        $daemon->loopactive = true;
        $daemon->loopelapsed = 0;

        // update pid reference
        $daemon->pid = ProcessTools::getPid();

        // install signals
        $daemon->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopWorker');
        $daemon->events->subscribe('daemon.posix.'.SIGINT, '\Comodojo\Daemon\Listeners\StopWorker');
        // $worker->instance->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopWorker');
        // $worker->instance->events->subscribe('daemon.posix.'.SIGINT, '\Comodojo\Daemon\Listeners\StopWorker');

        // launch daemon
        $worker->instance->spinup();

        // start looping
        while ($daemon->loopactive) {

            $signal = $worker->shared->read();
            if ( !empty($signal) ) {
                // $daemon->events->emit( new WorkerEvent($signal, $daemon, $worker->instance) );
                $worker->instance->events->emit( new WorkerEvent($signal, $daemon, $worker->instance) );
                $worker->shared->delete();
            }

            $start = microtime(true);

            // $daemon->events->emit( new WorkerEvent('loopstart', $daemon, $worker->instance) );
            $worker->instance->events->emit( new WorkerEvent('loopstart', $daemon, $worker->instance) );

            $worker->instance->loop();

            // $daemon->events->emit( new WorkerEvent('loopstop', $daemon, $worker->instance) );
            $worker->instance->events->emit( new WorkerEvent('loopstop', $daemon, $worker->instance) );

            $daemon->loopcount++;

            $daemon->loopelapsed = (microtime(true) - $start);

            $lefttime = $worker->looptime - $daemon->loopelapsed;

            if ( $lefttime > 0 ) usleep($lefttime * 1000000);

        }

        $worker->instance->spindown();

        $daemon->end(0);

    }

    public function stop($name = null) {

        foreach ($this->data as $wname => $worker) {

            if ( is_null($name) || $name == $wname ) {

                // close the shared memory block
                $worker->shared->close();

                // terminate the worker
                ProcessTools::term($worker->pid, 5, SIGTERM);

            }

        }

    }

    public function running($pid) {

        return ProcessTools::isRunning($pid);

    }

 }
