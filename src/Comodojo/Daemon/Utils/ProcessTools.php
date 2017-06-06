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
     * Terminate a process, asking PID to terminate or killing it directly.
     *
     * @param int $pid
     * @param int $lagger_timeout Timeout to wait before killing process if it refuses to terminate
     * @param int $signal Signal to send (default to SIGTERM)
     *
     * @return  bool
     */
    public static function term($pid, $lagger_timeout = 0, $signal = SIGTERM) {

        $kill_time = time() + $lagger_timeout;

        // $term = posix_kill($pid, $signal);
        $term = self::signal($pid, $signal);

        while ( time() < $kill_time ) {

            if ( !self::isRunning($pid) ) return $term;
            usleep(20000);

        }

        return self::kill($pid);

    }

    /**
     * Kill a process
     *
     * @param int $pid
     * @return bool
     */
    public static function kill($pid) {

        // return posix_kill($pid, SIGKILL);
        return self::signal($pid, SIGKILL);

    }

    public static function signal($pid, $signal = SIGUSR1) {

        return posix_kill($pid, $signal);

    }

    /**
     * Return true if process is still running, false otherwise
     *
     * @param int $pid
     * @return bool
     */
    public static function isRunning($pid) {

        return (pcntl_waitpid($pid, $status, WNOHANG) === 0);

    }

    /**
     * Get niceness of a running process
     *
     * @param int|null $pid The pid to query, or current process if null
     * @return int
     */
    public static function getNiceness($pid = null) {

        return pcntl_getpriority($pid);

    }

    /**
     * Set niceness of a running process
     *
     * @param int|null $pid The pid to query, or current process if null
     * @return bool
     */
    public static function setNiceness($niceness, $pid = null) {

        return is_null($pid) ? proc_nice($niceness) : pcntl_setpriority($pid, $$niceness);

    }

    /**
     * Get current process PID
     *
     * @return int
     */
    public static function getPid() {

        return posix_getpid();

    }

}
