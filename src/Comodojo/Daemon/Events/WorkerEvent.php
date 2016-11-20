<?php namespace Comodojo\Daemon\Events;

use \Comodojo\Daemon\Process;
use \Comodojo\Daemon\Worker\WorkerInterface;
use \Comodojo\Foundation\Events\AbstractEvent;

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

class WorkerEvent extends AbstractEvent {

    private $process;

    private $worker;

    public function __construct($signal, Process $process, WorkerInterface $worker) {

        parent::__construct("daemon.worker.$signal");

        $this->process = $process;

        $this->worker = $worker;

    }

    public function getProcess() {

        return $this->process;

    }

    public function getWorker() {

        return $this->worker;

    }

}
