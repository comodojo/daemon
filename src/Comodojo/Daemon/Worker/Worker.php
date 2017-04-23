<?php namespace Comodojo\Daemon\Worker;

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

    protected $pid;

    protected $instance;

    protected $looptime;

    protected $is_forever;

    protected $input_channel;

    protected $output_channel;

    public function getPid() {

        return $this->pid;

    }

    public function setPid($pid) {

        $this->pid = $pid;

        return $this;

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

    public function getInput() {

        return $this->input;

    }

    public function setInput($input) {

        $this->input = $input;

        return $this;

    }

    public function getOutput() {

        return $this->output;

    }

    public function setOutput($output) {

        $this->output = $output;

        return $this;

    }


    protected $data = array(
        'pid' => null,
        'instance' => null,
        'looptime' => 1,
        'forever' => false,
        'input' => null,
        'output' => null
    );

}
