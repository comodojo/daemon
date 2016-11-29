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

        // cleanup events
        $daemon->signals->any()->reset();

        // unmask signals (if restart)
        if ( $unmask === true ) {
            $daemon->signals->any()->unmask();
        }

        // inject events and logger
        $logger = $daemon->logger->withName($name);
        $events = new EventsManager($logger);
        $worker->instance->logger = $logger;
        $worker->instance->events = $events;

        $this->declassDaemon($daemon);

        // declare ticks and ticker
        declare(ticks=5);
        register_tick_function([$this, 'ticker'], $worker);

        // register internal listeners
        $events->subscribe('daemon.worker.close', '\Comodojo\Daemon\Listeners\StopWorker');

        // set worker components
        $daemon->loopcount = 0;
        $daemon->loopactive = true;
        $daemon->loopelapsed = 0;

        // update pid reference
        $daemon->pid = ProcessTools::getPid();

        // launch daemon
        $worker->instance->spinup();

        // start looping
        while ($daemon->loopactive) {

            $start = microtime(true);

            // $daemon->events->emit( new WorkerEvent('loopstart', $daemon, $worker->instance) );
            $events->emit( new WorkerEvent('loopstart', $daemon, $worker->instance) );

            $worker->instance->loop();

            $daemon->loopcount++;

            $daemon->loopelapsed = (microtime(true) - $start);

            // $daemon->events->emit( new WorkerEvent('loopstop', $daemon, $worker->instance) );
            $events->emit( new WorkerEvent('loopstop', $daemon, $worker->instance) );

            $lefttime = $worker->looptime - $daemon->loopelapsed;

            if ( $lefttime > 0 ) usleep($lefttime * 1000000);

        }

        $worker->instance->spindown();

        $daemon->end(0);

    }

    public function stop($name = null) {

        foreach ($this->data as $wname => $worker) {

            if ( is_null($name) || $name == $wname ) {

                // fix the wait time;
                $time = time() + 5;

                // try to gently ask the worker to close
                $worker->shared->send('close');

                while (time() < $time) {

                    if ( !$this->running($worker->pid) ) break;
                    usleep(20000);

                }

                // close the shared memory block
                $worker->shared->close();

                // terminate the worker if still alive
                if ($this->running($worker->pid)) ProcessTools::term($worker->pid, 5, SIGTERM);

            }

        }

    }

    public function running($pid) {

        return ProcessTools::isRunning($pid);

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

    public function ticker($worker) {

        $signal = $worker->shared->read();
        if ( !empty($signal) ) {
            $worker->instance->events->emit( new WorkerEvent($signal, $this->daemon, $worker->instance) );
            $worker->shared->delete();
        }

    }

 }
