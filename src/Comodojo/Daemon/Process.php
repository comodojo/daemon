<?php namespace Comodojo\Daemon;

use \Comodojo\Daemon\Events\PosixEvent;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Daemon\Utils\Checks;
use \Comodojo\Foundation\DataAccess\Model as DataModel;
use \Comodojo\Foundation\Events\Manager as EventsManager;
use \Comodojo\Foundation\Logging\Manager as LogManager;
use \Psr\Log\LoggerInterface;
use \RuntimeException;
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

abstract class Process extends DataModel {

    /**
     * Build the process
     *
     * @param int $niceness
     * @param EventsManager $events;
     * @param LoggerInterface $logger;
     *
     * @property EventsManager $events
     * @property LoggerInterface $logger
     * @property int $pid
     */
    public function __construct($niceness = null, LoggerInterface $logger = null, EventsManager $events = null){

        if ( !Checks::cli() ) {
            throw new RuntimeException("Process can run only in cli SAPI");
        }

        if ( !Checks::signals() ) {
            throw new Exception("Missing pcntl signaling");
        }

        $this->logger = is_null($logger) ? LogManager::create('daemon', false)->getLogger() : $logger;
        $this->events = is_null($events) ? EventsManager::create($this->logger) : $events;

        // get current PID
        $this->pid = ProcessTools::getPid();

        if ( ProcessTools::setNiceness($niceness) === false ) {
            $this->logger->warning("Unable to set process niceness to $niceness");
        }

        // register signals
        $this->registerSignals();

    }

    /**
     * Register signals
     * 
     * Signals should be catched by ticks or ad-hoc loop. Each POSIX
     * event will be exported as PosixEvent; SIGTERM and SIGINT will
     * stop the process.
     *
     */
    protected function registerSignals() {

        $pluggable_signals = array(
            SIGHUP, SIGCHLD, SIGUSR1, SIGUSR2, SIGILL, SIGTRAP, SIGABRT, SIGIOT,
            SIGBUS, SIGFPE, SIGSEGV, SIGPIPE, SIGALRM, SIGTTIN, SIGTTOU, SIGURG,
            SIGXCPU, SIGXFSZ, SIGVTALRM, SIGPROF, SIGWINCH, SIGIO, SIGSYS, SIGBABY,
            SIGTSTP, SIGCONT
        );

        if ( defined('SIGPOLL') )   $pluggable_signals[] = SIGPOLL;
        if ( defined('SIGPWR') )    $pluggable_signals[] = SIGPWR;
        if ( defined('SIGSTKFLT') ) $pluggable_signals[] = SIGSTKFLT;

        // register supported signals

        pcntl_signal(SIGTERM, array($this, 'sigTermHandler'));

        pcntl_signal(SIGINT, array($this, 'sigIntHandler'));

        // register pluggable signals

        foreach ( $pluggable_signals as $signal ) {

            pcntl_signal($signal, array($this, 'genericSignalHandler'));

        }

    }

    /**
     * The sigTerm handler.
     *
     * It kills everything and then exit with status 0
     */
    public function sigIntHandler($signal) {

        if ( $this->pid == ProcessTools::getPid() ) {

            $this->logger->info("Received TERM signal, shutting down process gracefully");

            $this->events->emit( new PosixEvent($signal, $this) );

            $this->end(0);

        }

    }

    /**
     * The sigTerm handler.
     *
     * It kills everything and then exit with status 1
     */
    public function sigTermHandler($signal) {

        if ( $this->pid == ProcessTools::getPid() ) {

            $this->logger->info("Received TERM signal, shutting down process");

            $this->events->emit( new PosixEvent($signal, $this) );

            $this->end(1);

        }

    }

    /**
     * The generig signal handler.
     *
     * It can be used to catch custom signals using events
     */
    public function genericSignalHandler($signal) {

        if ( $this->pid == ProcessTools::getPid() ) {

            $this->logger->info("Received $signal signal, firing associated event(s)");

            $this->events->emit( new PosixEvent($signal, $this) );

        }

    }

    /**
     * @param integer $return_code
     */
    public function end($return_code) {

        exit($return_code);

        // if ( $this->configuration->get('is-test') === true ) {
        //
        //     if ( $return_code === 1 ) throw new Exception("Test Exception");
        //
        //     else return $return_code;
        //
        // }

    }

}
