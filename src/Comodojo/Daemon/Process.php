<?php namespace Comodojo\Daemon;

use \Comodojo\Daemon\Events\PosixEvent;
use \Comodojo\Daemon\Utils\ProcessTools;
use \Comodojo\Daemon\Utils\Checks;
use \Comodojo\Daemon\Utils\PosixSignals;
use \Comodojo\Daemon\Traits\EventsTrait;
use \Comodojo\Daemon\Traits\LoggerTrait;
use \Comodojo\Daemon\Traits\SignalsTrait;
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

abstract class Process {

    use EventsTrait;
    use LoggerTrait;
    use SignalsTrait;

    protected $pid;

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
        $this->signals = new PosixSignals();
        $this->signals->any()->call(array($this, 'signalToEvent'));

    }

    public function getPid() {

        return $this->pid;

    }

    public function setPid($pid) {

        $this->pid = $pid;

    }

    /**
     * The generig signal handler.
     *
     * It can be used to catch custom signals using events
     */
    public function signalToEvent($signal) {

        if ( $this->pid == ProcessTools::getPid() ) {

            $signame = $this->signals->signame($signal);

            $this->logger->info("Received $signame ($signal) signal, firing associated event(s)");

            $this->events->emit( new PosixEvent($signal, $this) );
            $this->events->emit( new PosixEvent($signame, $this) );

        }

    }

    /**
     * @param integer $return_code
     */
    public function end($return_code) {

        exit($return_code);

    }

}
