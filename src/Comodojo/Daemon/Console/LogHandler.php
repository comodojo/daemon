<?php namespace Comodojo\Daemon\Console;

use \Monolog\Logger;
use \Monolog\Handler\AbstractProcessingHandler;
use \League\CLImate\CLImate;

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

class LogHandler extends AbstractProcessingHandler {

    private $outputcontroller = null;

    private static $colors = array(
        100 => 'light_green',
        200 => 'green',
        250 => 'light_yellow',
        300 => 'yellow',
        400 => 'light_red',
        500 => 'red',
        550 => 'light_magenta',
        600 => 'magenta',
    );

    public function __construct($level = Logger::DEBUG, $bubble = true) {

        $this->outputcontroller = new CLImate();

        parent::__construct($level, $bubble);

    }

    protected function write(array $record) {

        $level = $record['level'];

        $message = $record['formatted'];

        $context = empty($record['context']) ? null : $record['context'];

        $time = $record['datetime']->format('c');

        $this->toConsole($time, $level, $message, $context);

    }

    private function toConsole($time, $level, $message, $context) {

        $color = static::$colors[$level];

        $pattern = "<%s>%s</%s>";

        $message = sprintf($pattern, $color, $message, $color);

        $this->outputcontroller->out($message);

        //if ( !empty($context) ) $this->outputcontroller->out(sprintf($pattern, $color, var_export($context, true), $color));

    }

}
