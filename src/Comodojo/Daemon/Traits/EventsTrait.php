<?php namespace Comodojo\Daemon\Traits;

use \Comodojo\Foundation\Events\Manager as EventsManager;

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

trait EventsTrait {

    /**
     * Current events manager
     *
     * @var EventsManager
     */
    protected $events;

    /**
     * Get current events manager.
     *
     * @return EventsManager
     */
    public function getEvents() {

        return $this->events;

    }

    /**
     * Set current events manager
     *
     * @param EventsManager $events
     * @return self
     */
    public function setEvents(EventsManager $events) {

        $this->events = $events;

        return $this;

    }

}
