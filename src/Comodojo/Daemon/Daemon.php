<?php namespace Comodojo\Daemon;

//declare(ticks=1);

use \Comodojo\Daemon\Utils\PropertiesValidator;
use \Comodojo\Daemon\Utils\Checks;
use \Comodojo\Daemon\Socket\Server as SocketServer;
use \Comodojo\Daemon\Worker\Manager as WorkerManager;
use \Comodojo\Daemon\Worker\Worker;
use \Comodojo\Daemon\Locker\PidLock;
use \Comodojo\Daemon\Listeners\WorkerWatchdog;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Daemon\Console\LogHandler;
use \Comodojo\Foundation\Utils\ClassProperties;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Psr\Log\LoggerInterface;
use \League\CLImate\CLImate;
use \Comodojo\Exception\SocketException;
use \Exception;

// use \Comodojo\Extender\Components\PidLock;
// use \Comodojo\Extender\Components\RunLock;
// use \Comodojo\Extender\Events\DaemonEvent;
// use \Comodojo\Extender\Listeners\PauseDaemon;
// use \Comodojo\Extender\Listeners\ResumeDaemon;
// use \Comodojo\Extender\Utils\Checks;
// use \Comodojo\Extender\Utils\Validator;
// use \Comodojo\Dispatcher\Components\Configuration;
// use \Comodojo\Cache\Cache;
// use \Comodojo\Dispatcher\Components\EventsManager;

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

abstract class Daemon extends Process {

    protected static $default_properties = array(
        'pidfile' => 'daemon.pid',
        'socketfile' => 'unix://daemon.sock',
        'socketbuffer' => 8192,
        'niceness' => 0,
        'arguments' => '\\Comodojo\\Daemon\\Console\\DaemonArguments',
        'description' => 'Comodojo Daemon'
    );

    public function __construct($properties = [], LoggerInterface $logger = null, EventsManager $events = null){

        if ( !Checks::multithread() ) {
            throw new Exception("Missing pcntl fork");
        }

        $properties = ClassProperties::create(self::$default_properties)->merge($properties);

        parent::__construct($properties->niceness, $logger, $events);

        // prepare the pidlock
        $this->pidlock = new PidLock($properties->pidfile);

        // init the socket
        $this->socket = new SocketServer(
            $properties->socketfile,
            $this->logger,
            $this->events,
            $this,
            $properties->socketbuffer
        );

        // init the worker manager
        $this->workers = new WorkerManager($this->logger, $this->events);

        // init the console
        $this->console = new CLImate();
        $this->console->description($properties->description);
        $this->console->arguments->add( $properties->arguments::create()->export() );

    }

    abstract public function setup();

    public function init() {

        $this->console->arguments->parse();

        if ( $this->console->arguments->defined('help') ) {
            // show help and exit
            $this->console->usage();
            $this->end(0);
        } else if ( $this->console->arguments->defined('foreground') ) {
            if ( $this->console->arguments->defined('verbose') ) {
                $this->logger->pushHandler(new LogHandler());
            }
            $this->start();
        } else {
            // run extender as a normal foreground process
            $this->daemonize();
        }

    }

    public function daemonize() {

        $pid = $this->fork();

        $this->detach();

        // update pid reference (we have a new daemon)
        $this->pid = $pid;

        // autostart daemon
        $this->start();

    }

    public function start() {

        foreach ($this->workers->get() as $name => $worker) {

            $this->workers->setPid($name, $this->launch($worker));

        }

        $this->becomeSupervisor();

        $this->setup();

        try {

            $this->socket->listen();

        } catch (SocketException $e) {

            $this->stop();

        }

        $this->end(0);

    }

    public function stop() {

        $this->logger->notice("Stopping daemon...");

        $this->workers->stop();

        $this->socket->stop();

        $this->pidlock->release();

    }

    private function becomeSupervisor() {

        $this->pidlock->lock($this->pid);
        $this->socket->connect();

        // $this->events->subscribe('daemon.posix.'.SIGINT, '\Comodojo\Daemon\Listeners\StopDaemon');
        // $this->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopDaemon');

        //$this->events->addListener('daemon.socket.loop', new WorkerWatchdog());
        $this->events->subscribe('daemon.socket.loop', '\Comodojo\Daemon\Listeners\WorkerWatchdog');

        // if ( $this->worker !== null ) {
        //     $this->events->subscribe('daemon.socket.loop', '\Comodojo\Daemon\Listeners\WorkerWatchdog');
        // }

        //pcntl_signal_dispatch();

    }

    private function createWorker() {

        // init worker (if any)
        //     - fork worker
        //     - unset pidlock and socket components
        //     - spinup worker
        //     - attach posix signals
        //     - start worker loop

        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error('Could not create worker (fork error)');
            $this->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Worker created with pid $pid");
            return $pid;
        }

        unset($this->pidlock);
        // This will not work
        // unset($this->socket);

        $this->pid = ProcessTools::getPid();

        $this->worker->spinup();

        $this->worker->loop();

    }

    private function fork() {

        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error('Could not create daemon (fork error)');
            $this->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Daemon created with pid $pid");
            $this->end(0);
        }

        return ProcessTools::getPid();

    }

    private function detach() {

        if (is_resource(STDOUT)) fclose(STDOUT);
        if (is_resource(STDERR)) fclose(STDERR);
        if (is_resource(STDIN)) fclose(STDIN);

        // become a session leader
        $sid = posix_setsid();

        if ( $sid < 0 ) {
            $this->logger->error("Unable to become session leader");
            $this->end(1);
        }

    }

    private function launch(Worker $worker) {

        $name = $worker->instance->getName();

        // fork worker
        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error("Could not create worker $name (fork error)");
            $this->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Worker $name created with pid $pid");
            return $pid;
        }

        // declare ticks
        declare(ticks=5);

        // unset supervisor components
        unset($this->socket);
        unset($this->workers);
        unset($this->console);

        // set worker components
        $this->loopcount = 0;
        $this->loopactive = true;
        $this->loopelapsed = 0;

        // update pid reference
        $this->pid = ProcessTools::getPid();

        // install signals
        $this->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopWorker');

        // launch daemon
        $worker->instance->spinup();

        while ($this->loopactive) {

            $start = microtime(true);

            // pcntl_signal_dispatch();

            // $this->events->emit( new DaemonEvent('preloop', $this) );

            // if ( $this->runlock->check() && $this->loopactive) {

                // $this->events->emit( new DaemonEvent('loopstart', $this) );

                $worker->instance->loop();

                // $this->events->emit( new DaemonEvent('loopstop', $this) );

                $this->loopcount++;

            // }

            // $this->events->emit( new DaemonEvent('postloop', $this) );

            $this->loopelapsed = (microtime(true) - $start);

            $lefttime = $worker->looptime - $this->loopelapsed;

            if ( $lefttime > 0 ) usleep($lefttime * 1000000);

        }

        $worker->instance->spindown();

        $this->end(0);

    }

}
