<?php namespace Comodojo\Daemon;

//declare(ticks=1);

use \Comodojo\Daemon\Utils\PropertiesValidator;
use \Comodojo\Daemon\Utils\Checks;
use \Comodojo\Daemon\Socket\Server as SocketServer;
// use \Comodojo\Daemon\Worker\Manager as WorkerManager;
use \Comodojo\Daemon\Locker\PidLock;
use \Comodojo\Daemon\Listeners\WorkerWatchdog;
use \Comodojo\Daemon\Utils\ProcessTools;
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
        'socketbuffer' => 4096,
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

        // prepare the socket
        // $this->socket = SocketServer::create(
        //     $properties->socketfile,
        //     $this->logger,
        //     $this->events,
        //     $this,
        //     $properties->socketbuffer
        // );
        $this->socket = new SocketServer(
            $properties->socketfile,
            $this->logger,
            $this->events,
            $this,
            $properties->socketbuffer
        );

        // init the console
        $this->console = new CLImate();
        $this->console->description($properties->description);
        $this->console->arguments->add( $properties->arguments::create()->export() );

    }

    abstract public function setup();

    public function init() {

        $this->console->arguments->parse();

        if ( $this->console->arguments->get('help') === true ) {
            // show help and exit
            $this->console->usage();
            $this->end(0);
        } else if ( $this->console->arguments->get('foreground') === true ) {
            // run extender as a deamon
            $this->start();
        } else {
            // run extender as a normal foreground process
            $this->daemonize();
        }

    }

    public function daemonize() {

        $pid = pcntl_fork();

        if ( $pid == -1 ) {
            $this->logger->error('Could not create daemon (fork error)');
            $this->cleanup = false;
            $this->end(1);
        }

        if ( $pid ) {
            $this->logger->info("Daemon created with pid $pid");
            $this->cleanup = false;
            $this->end(0);
        }

        // update pid reference (we have a new daemon)
        $this->pid = ProcessTools::getPid();

        // become a session leader
        posix_setsid();

        // autostart daemon
        $this->start();

    }

    public function start() {

        // init worker (if any)
        //     - unset pidlock and socket components
        //     - spinup worker
        //     - attach posix signals
        //     - start worker loop

        $this->pidlock->lock($this->pid);
        $this->socket->connect();

        $this->setup();

        $this->becomeSupervisor();

        try {

            $this->socket->listen();

        } catch (SocketException $e) {

            $this->stop();

        }

        $this->end(0);

    }

    public function stop() {

        $this->socket->stop();
        $this->pidlock->release();

    }

    private function becomeSupervisor() {

        // $this->events->subscribe('daemon.posix.'.SIGINT, '\Comodojo\Daemon\Listeners\StopDaemon');
        // $this->events->subscribe('daemon.posix.'.SIGTERM, '\Comodojo\Daemon\Listeners\StopDaemon');

        //$this->events->addListener('daemon.socket.loop', new WorkerWatchdog());
        $this->events->subscribe('daemon.socket.loop', '\Comodojo\Daemon\Listeners\WorkerWatchdog');

        // if ( $this->worker !== null ) {
        //     $this->events->subscribe('daemon.socket.loop', '\Comodojo\Daemon\Listeners\WorkerWatchdog');
        // }

        //pcntl_signal_dispatch();

    }

}
