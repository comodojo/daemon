<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Events\WorkerEvent;
use \Comodojo\Foundation\Validation\DataFilter;
use \Psr\Log\LoggerInterface;
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

class Loop implements Countable {

    const SPINUP = 0;
    const LOOPING = 1;
    const PAUSED = 2;
    const SPINDOWN = 3;

    private $active = 1;

    private $paused;

    private $status;

    private $count = 0;

    private $looptime = 1;

    private $events;

    private $worker;

    public function __construct(Worker $worker) {

        $this->worker = $worker;

        $this->events = $worker->instance->events;

        $this->looptime = DataFilter::filterInteger($worker->looptime, $min=1, $max=PHP_INT_MAX, $default=1);

        $this->events->subscribe('daemon.worker.stop', '\Comodojo\Daemon\Listeners\StopWorker');
        $this->events->subscribe('daemon.worker.pause', '\Comodojo\Daemon\Listeners\PauseWorker');
        $this->events->subscribe('daemon.worker.resume', '\Comodojo\Daemon\Listeners\ResumeWorker');

        register_tick_function([$this, 'ticker']);

    }

    public function start() {

        declare(ticks=5);

        if ( $this->status !== null ) {
            throw new Exception("Already looping");
        }

        // spinup daemon
        $this->setStatus(self::SPINUP);
        $this->worker->instance->spinup();

        // start looping
        $this->setStatus(self::LOOPING);
        while ($this->active) {

            if ( $this->paused ) {
                $this->setStatus(self::PAUSED);
                sleep($this->looptime);
                continue;
            }

            $this->setStatus(self::LOOPING);

            $start = microtime(true);

            $this->events->emit( new WorkerEvent('loopstart', $this, $this->worker) );

            $this->worker->instance->loop();

            ++$this->count;

            $elapsed = (microtime(true) - $start);

            $this->events->emit( new WorkerEvent('loopstop', $this, $this->worker) );

            $lefttime = $this->looptime - $elapsed;

            if ( $lefttime > 0 ) usleep($lefttime * 1000000);

        }

        $this->setStatus(self::SPINDOWN);

        // spindown worker
        $this->worker->instance->spindown();

        return;

    }

    public function stop() {

        $this->active = false;

    }

    public function pause() {

        $this->pause = true;

    }

    public function resume() {

        $this->pause = false;

    }

    public function count() {

        return $this->count;

    }

    public function ticker() {

        $signal = $this->worker->output->read();

        if ( !empty($signal) ) {
            $this->events->emit( new WorkerEvent($signal, $this, $this->worker) );
            $this->worker->output->delete();
        }

    }

    private function setStatus($status) {
        $this->status = $status;
        return $this->worker->input->send($status);
    }

}
