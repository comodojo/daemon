<?php namespace Comodojo\Daemon\Listeners;

use \League\Event\AbstractListener;
use \League\Event\EventInterface;

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

class WorkerWatchdog extends AbstractListener {

    public function handle(EventInterface $event) {

        $daemon = $event->getProcess();

        $workers = $daemon->getWorkers();
        $logger = $daemon->getLogger();

        foreach ($workers as $name => $worker) {

            if ($workers->running($worker->getPid())) {

                $logger->debug("Worker $name seems to be running");

            } else {

                $logger->debug("Worker $name has exited");

                if ($worker->getForever()) {
                    $logger->debug("Attempting to restart $name");
                    $workers->start($name, true);
                } else {
                    $logger->error("Worker $name has exited, shutting down daemon");
                    $daemon->stop();
                }

            }

        }

    }

}
