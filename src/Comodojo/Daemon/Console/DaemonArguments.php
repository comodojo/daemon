<?php namespace Comodojo\Daemon\Console;

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

class DaemonArguments extends AbstractArguments {

    protected $data = array(
        'verbose' => [
            'prefix' => 'v',
            'longPrefix' => 'verbose',
            'description' => 'turn verbose mode on (only in foreground mode)',
            'required' => false,
            'noValue' => true
        ],
        'daemon' => [
            'prefix' => 'd',
            'longPrefix' => 'daemon',
            'description' => 'run as a daemon',
            'required' => false,
            'noValue' => true
        ],
        'foreground' => [
            'prefix' => 'f',
            'longPrefix' => 'foreground',
            'description' => 'run as a foreground process',
            'required' => false,
            'noValue' => true
        ],
        'hardstart' => [
            'prefix' => 's',
            'longPrefix' => 'hardstart',
            'description' => 'Attempt to clean sock and lock files before startup',
            'required' => false,
            'noValue' => true
        ],
        'help' => [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'show this help (default)',
            'required' => false,
            'noValue' => true
        ]
    );

}
