<?php namespace Comodojo\Daemon\Worker;

use \Comodojo\Daemon\Traits\PidTrait;

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

class Worker {

    use PidTrait;

    protected $instance;

    protected $looptime;

    protected $is_forever;

    protected $input_channel;

    protected $output_channel;

    public static function create() {

        return new Worker();

    }

    public function getInstance() {

        return $this->instance;

    }

    public function setInstance($instance) {

        $this->instance = $instance;

        return $this;

    }

    public function getLooptime() {

        return $this->looptime;

    }

    public function setLooptime($looptime) {

        $this->looptime = $looptime;

        return $this;

    }

    public function getForever() {

        return $this->forever;

    }

    public function setForever($forever) {

        $this->forever = $forever;

        return $this;

    }

    public function getInputChannel() {

        return $this->input_channel;

    }

    public function setInputChannel($input) {

        $this->input_channel = $input;

        return $this;

    }

    public function getOutputChannel() {

        return $this->output_channel;

    }

    public function setOutputChannel($output) {

        $this->output_channel = $output;

        return $this;

    }

}
