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

class ProcessTools {

    /**
     * Terminate a process
     *
     * @return  bool
     */
    public static function term($pid, $lagger_timeout = 0, $signal = SIGTERM) {

        $kill_time = time() + $lagger_timeout;

        $term = posix_kill($pid, $signal);

        while ( time() < $kill_time ) {

            if ( !self::isRunning($pid) ) return $term;
            usleep(20000);

        }

        return self::kill($pid);

    }

    /**
     * Kill a process
     *
     * @return  bool
     */
    public static function kill($pid) {

        return posix_kill($pid, SIGKILL);

    }

    /**
     * Return true if process is still running, false otherwise
     *
     * @return  bool
     */
    public static function isRunning($pid) {

        return (pcntl_waitpid($pid, $status, WNOHANG) === 0);

    }

    public static function getNiceness($pid = null) {

        return pcntl_getpriority($pid);

    }

    public static function setNiceness($niceness, $pid = null) {

        return is_null($pid) ? proc_nice($niceness) : pcntl_setpriority($pid, $$niceness);

    }

    public static function getPid() {

        return posix_getpid();

    }

}
