<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\Foundation\DataAccess\Model;

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

abstract class AbstractMessage extends Model {

    protected $mode = self::PROTECTDATA;

    public function __toString() {

        return var_export($this->data, true);

    }

    public function serialize() {

        return base64_encode(serialize($this->data));

    }

    public function unserialize($serialized) {

        $data = unserialize(base64_decode($serialized));

        return $this->merge($data);

    }

}
