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

interface WorkerInterface {

    /**
     * Get worker id (unique).
     *
     * @return string
     */
    public function getId();

    /**
     * Get worker name.
     *
     * @return string
     */
    public function getName();

    /**
     * Spinup worker
     *
     * @return null
     */
    public function spinup();

    /**
     * The worker loop
     *
     * This method will be called inside the main daemon loop
     *
     * @return null
     */
    public function loop();

    /**
     * Spindown worker
     *
     * @return null
     */
    public function spindown();

}
