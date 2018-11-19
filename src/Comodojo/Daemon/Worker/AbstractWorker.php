<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Foundation\Events\EventsTrait;
use \Comodojo\Foundation\Logging\LoggerTrait;
use \Comodojo\Daemon\Traits\SignalsTrait;
use \Psr\Log\LoggerInterface;
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

abstract class AbstractWorker implements WorkerInterface {

    use EventsTrait;
    use LoggerTrait;
    use SignalsTrait;

    private $name;

    private $id;

    public function __construct($name = null) {

        $this->id = uniqid();

        $this->name = empty($name) ? 'worker.'.$this->id : $name;

    }

    /**
     * {@inheritdoc}
     */
    public function getName() {

        return $this->name;

    }

    /**
     * {@inheritdoc}
     */
    public function getId() {

        return $this->id;

    }

    /**
     * {@inheritdoc}
     */
    public function spinup() {}

    /**
     * {@inheritdoc}
     */
    public function spindown() {}

    /**
     * {@inheritdoc}
     */
    abstract public function loop();

}
