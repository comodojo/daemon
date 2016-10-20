<?php namespace Comodojo\Daemon\Utils;

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

class Checks {

    /**
     * Check if script is running from command line
     *
     * @return  bool
     */
    final public static function cli() {

        return php_sapi_name() === 'cli';

    }

    /**
     * Check if php interpreter supports pcntl_fork (required in multithread mode)
     *
     * @return  bool
     */
    final public static function multithread() {

        return function_exists("pcntl_fork");

    }

    /**
     * Check if php interpreter supports pcntl signal handlers
     *
     * @return  bool
     */
    final public static function signals() {

        return function_exists("pcntl_signal");

    }

}
