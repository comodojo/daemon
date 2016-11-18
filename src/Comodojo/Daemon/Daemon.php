<?php namespace Comodojo\Daemon;

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
        'sockettimeout' => 15,
        'niceness' => 0,
        'arguments' => '\\Comodojo\\Daemon\\Console\\DaemonArguments',
        'description' => 'Comodojo Daemon'
    );

    /**
     * Daemon constructor
     *
     * @param array $properties
     * @param LoggerInterface $logger;
     * @param EventsManager $events;
     *
     * @property EventsManager $events
     * @property LoggerInterface $logger
     * @property int $pid
     */
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
            $properties->socketbuffer,
            $properties->sockettimeout
        );

        // init the worker manager
        $this->workers = new WorkerManager($this->logger, $this->events, $this);

        // init the console
        $this->console = new CLImate();
        $this->console->description($properties->description);
        $args = new $properties->arguments;
        $this->console->arguments->add( $args::create()->export() );

    }

    /**
     * Setup method; it allows to inject code BEFORE the daemon spinup
     *
     */
    abstract public function setup();

    /**
     * Parse console arguments and init the daemon
     *
     */
    public function init() {

        $args = $this->console->arguments;

        $args->parse();

        if ( $args->defined('hardstart') ) {

            $this->hardstart();

        }

        if ( $args->defined('daemon') ) {

            $this->daemonize();

        } else if ( $args->defined('foreground') ) {

            if ( $args->defined('verbose') ) {
                $this->logger->pushHandler(new LogHandler());
            }

            $this->start();

        } else {

            $this->console->pad()->green()->usage();
            $this->end(0);

        }

    }

    /**
     * Start as a daemon, forking main process and detaching it from terminal
     *
     */
    public function daemonize() {

        // fork script
        $pid = $this->fork();

        // detach from current terminal (if any)
        $this->detach();

        // update pid reference (we have a new daemon)
        $this->pid = $pid;

        // start process daemon
        $this->start();

    }

    /**
     * Start the process, creating socket and spinning up workers (if any)
     *
     */
    public function start() {

        foreach ($this->workers as $name => $worker) {

            $this->workers->setPid($name, $this->workers->start($name));

        }

        $this->becomeSupervisor();

        $this->setup();

        try {

            $this->socket->listen();

        } catch (SocketException $e) {

            if ( $this->supervisor ) $this->stop();

        }

        if ( $this->supervisor ) {
            $this->stop();
            $this->end(0);
        }

        //$this->end(0);

    }

    public function stop() {

        $this->logger->notice("Stopping daemon...");

        $this->workers->stop();

        $this->socket->stop();

        $this->pidlock->release();

    }

    private function becomeSupervisor() {

        $this->logger->notice("Initing supervisor subsystem");

        $this->supervisor = true;

        // lock current PID
        $this->pidlock->lock($this->pid);

        try {

            // connect socket
            $this->socket->connect();

        } catch (SocketException $e) {

            $this->logger->error("Supervisor error: ".$e->getMessage());
            $this->logger->notice("Shutting down process and childs");

            $this->stop();
            $this->end(1);

        }

        // Subscribe term events that could be catched
        $this->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopDaemon');
        $this->events->subscribe('daemon.posix.'.SIGINT, '\Comodojo\Daemon\Listeners\StopDaemon');

        // subscribe the WorkerWatchdog (if workers > 0)
        // TODO: this should be better coded as event catcher on SIGCHLD
        if ( count($this->workers) > 0 ) {
            $this->events->subscribe('daemon.socket.loop', '\Comodojo\Daemon\Listeners\WorkerWatchdog');
        }

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

    private function hardstart() {

        $this->pidlock->release();
        $this->socket->clean();

    }
}
