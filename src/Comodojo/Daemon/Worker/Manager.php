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

        $worker = $this->get($name);
        $daemon = $this->daemon;

        // TODO: inject logger and events directly from daemon (not in worker constructor)
        $daemon->logger = $daemon->logger->withName('worker');
        $worker->instance->logger = $daemon->logger;

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

        // set worker components
        $daemon->loopcount = 0;
        $daemon->loopactive = true;
        $daemon->loopelapsed = 0;

        // update pid reference
        $daemon->pid = ProcessTools::getPid();

        // install signals
        $this->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopWorker');

        // declare ticks
        declare(ticks=5);

        // launch daemon
        $worker->instance->spinup();

        register_shutdown_function(array($worker->instance, 'spindown'));

        while ($daemon->loopactive) {

            $start = microtime(true);

            // pcntl_signal_dispatch();

            // $this->events->emit( new DaemonEvent('preloop', $this) );

            // if ( $this->runlock->check() && $this->loopactive) {

                // $this->events->emit( new DaemonEvent('loopstart', $this) );

                $worker->instance->loop();

                // $this->events->emit( new DaemonEvent('loopstop', $this) );

                $daemon->loopcount++;

            // }

            // $this->events->emit( new DaemonEvent('postloop', $this) );

            $daemon->loopelapsed = (microtime(true) - $start);

            $lefttime = $worker->looptime - $daemon->loopelapsed;

            if ( $lefttime > 0 ) usleep($lefttime * 1000000);

        }

        // $worker->instance->spindown();

        $daemon->end(0);

    }

    public function stop($pid = null) {

        if ( empty($pid) ) {

            foreach ($this->data as $worker) {

                // posix_kill($worker->pid, SIGTERM);
                ProcessTools::term($worker->pid, 5, SIGINT);

            }

        } else {


        }

    }

    public function running($pid) {

        return ProcessTools::isRunning($pid);

    }

 }
