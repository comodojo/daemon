<?php namespace Comodojo\Daemon\Utils;

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

class PosixSignals {

    private $signals = [
        SIGHUP => 'SIGHUP',
        SIGCHLD => 'SIGCHLD',
        SIGUSR1 => 'SIGUSR1',
        SIGUSR2 => 'SIGUSR2',
        SIGILL => 'SIGILL',
        SIGTRAP => 'SIGTRAP',
        SIGABRT => 'SIGABRT',
        SIGIOT => 'SIGIOT',
        SIGBUS => 'SIGBUS',
        SIGFPE => 'SIGFPE',
        SIGSEGV => 'SIGSEGV',
        SIGPIPE => 'SIGPIPE',
        SIGALRM => 'SIGALRM',
        SIGTTIN => 'SIGTTIN',
        SIGTTOU => 'SIGTTOU',
        SIGURG => 'SIGURG',
        SIGXCPU => 'SIGXCPU',
        SIGXFSZ => 'SIGXFSZ',
        SIGVTALRM => 'SIGVTALRM',
        SIGPROF => 'SIGPROF',
        SIGWINCH => 'SIGWINCH',
        SIGIO => 'SIGIO',
        SIGSYS => 'SIGSYS',
        SIGBABY => 'SIGBABY',
        SIGTSTP => 'SIGTSTP',
        SIGCONT => 'SIGCONT'
    ];

    public function __construct() {

        if ( defined('SIGPOLL') ) $this->signal[SIGPOLL] = 'SIGPOLL';
        if ( defined('SIGPWR') ) $this->signal[SIGPWR] = 'SIGPWR';
        if ( defined('SIGSTKFLT') ) $this->signal[SIGSTKFLT] = 'SIGSTKFLT';
        if ( defined('SIGTERM') ) $this->signal[SIGTERM] = 'SIGTERM';
        if ( defined('SIGINT') ) $this->signal[SIGINT] = 'SIGINT';
        if ( defined('SIGKILL') ) $this->signal[SIGKILL] = 'SIGKILL';

    }

    public function getSignals() {

        return $this->signals;

    }

    public function getSigNo($signame) {

        $reverse_signals = array_flip($this->signals);

        return array_key_exists($signame, $reverse_signals) ? $reverse_signals[$signame] : null;

    }

    public function getSigName($signo) {

        return array_key_exists($signo, $this->signals) ? $this->signals[$signo] : null;

    }

}
