<?php namespace Comodojo\Daemon\Console;

use \Comodojo\Foundation\DataAccess\ArrayAccessTrait;
use \ArrayAccess;

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

abstract class AbstractArguments implements ArrayAccess {

    use ArrayAccessTrait;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Export data as array
     *
     * @return array
     */
    public function export() {

        return $this->data;

    }

    /**
     * Static constructor
     *
     * @return self
     */
    public static function create() {

        $class = get_called_class();

        return new $class();

    }

}
