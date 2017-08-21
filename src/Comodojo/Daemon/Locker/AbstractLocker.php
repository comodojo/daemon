<?php namespace Comodojo\Daemon\Locker;

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

abstract class AbstractLocker {

    abstract public function lock($what);

    abstract public function release();

    protected static function writeLock($file, $data) {

        $lock = file_put_contents($file, $data);

        if ( $lock === false ) throw new Exception("Cannot write lock file");

        return $lock;

    }

    protected static function readLock($file) {

        $data = file_get_contents($file);

        if ( $data === false ) throw new Exception("Cannot read lock file");

        return $data;

    }

    protected static function releaseLock($file) {

        $lock = file_exists($file) ? unlink($file) : true;

        return $lock;

    }

}
