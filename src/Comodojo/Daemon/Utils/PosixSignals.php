<?php namespace Comodojo\Daemon\Utils;

use \Comodojo\Foundation\DataAccess\IteratorTrait;
use \Comodojo\Foundation\DataAccess\CountableTrait;
use \Comodojo\Foundation\DataAccess\ArrayAccessTrait;
use \Iterator;
use \Countable;
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

class PosixSignals implements Iterator, Countable, ArrayAccess {

    use IteratorTrait;
    use CountableTrait;
    use ArrayAccessTrait;

    protected $data = [
        SIGHUP => 'SIGHUP',
        SIGINT => 'SIGINT',
        SIGQUIT => 'SIGQUIT',
        SIGILL => 'SIGILL',
        SIGTRAP => 'SIGTRAP',
        SIGABRT => 'SIGABRT',
        SIGIOT => 'SIGIOT',
        SIGBUS => 'SIGBUS',
        SIGFPE => 'SIGFPE',
        // SIGKILL => 'SIGKILL',
        SIGUSR1 => 'SIGUSR1',
        SIGSEGV => 'SIGSEGV',
        SIGUSR2 => 'SIGUSR2',
        SIGPIPE => 'SIGPIPE',
        SIGALRM => 'SIGALRM',
        SIGTERM => 'SIGTERM',
        SIGSTKFLT => 'SIGSTKFLT',
        SIGCLD => 'SIGCLD',
        SIGCHLD => 'SIGCHLD',
        SIGCONT => 'SIGCONT',
        // SIGSTOP => 'SIGSTOP',
        SIGTSTP => 'SIGTSTP',
        SIGTTIN => 'SIGTTIN',
        SIGTTOU => 'SIGTTOU',
        SIGURG => 'SIGURG',
        SIGXCPU => 'SIGXCPU',
        SIGXFSZ => 'SIGXFSZ',
        SIGVTALRM => 'SIGVTALRM',
        SIGPROF => 'SIGPROF',
        SIGWINCH => 'SIGWINCH',
        SIGPOLL => 'SIGPOLL',
        // SIGIO => 'SIGIO',
        SIGPWR => 'SIGPWR',
        SIGSYS => 'SIGSYS',
        SIGBABY => 'SIGBABY'
    ];

    protected $pointer;

    // public function __construct() {
    //
    //     if ( defined('SIGPOLL') ) $this->signal[SIGPOLL] = 'SIGPOLL';
    //     if ( defined('SIGPWR') ) $this->signal[SIGPWR] = 'SIGPWR';
    //     if ( defined('SIGSTKFLT') ) $this->signal[SIGSTKFLT] = 'SIGSTKFLT';
    //     if ( defined('SIGTERM') ) $this->signal[SIGTERM] = 'SIGTERM';
    //     if ( defined('SIGINT') ) $this->signal[SIGINT] = 'SIGINT';
    //     if ( defined('SIGKILL') ) $this->signal[SIGKILL] = 'SIGKILL';
    //
    // }

    public function sigNo($signame) {

        $reverse_signals = array_flip($this->data);

        return array_key_exists($signame, $reverse_signals) ? $reverse_signals[$signame] : null;

    }

    public function sigName($signo) {

        return array_key_exists($signo, $this->data) ? $this->data[$signo] : null;

    }

    public function on($signal) {

        if ( !isset($this[$signal]) ) throw new Exception("Signal $signal not supported");

        $this->pointer = $signal;

        return $this;

    }

    public function any() {

        $this->pointer = null;

        return $this;

    }

    public function call($callable) {

        if ( $this->pointer === null ) {
            $result = [];
            foreach ($this as $signo => $signame) {
                $result[] = $re = pcntl_signal($signo, $callable);
                if (!$re) echo "\n>>>Signal $signo (".$this->sigName($signo).") failed\n";
            }
            return !in_array(false, $result);
        }

        return pcntl_signal($this->pointer, $callable);

    }

    public function reset() {

        if ( $this->pointer === null ) {
            $result = [];
            foreach ($this as $signo => $signame) {
                $result[] = pcntl_signal($signo, SIG_DFL);
            }
            return !in_array(false, $result);
        }

        return pcntl_signal($this->pointer, SIG_DFL);

    }

    public function mask() {

        if ( $this->pointer === null ) {
            return pcntl_sigprocmask(SIG_SETMASK, $this);
        }

        return pcntl_sigprocmask(SIG_BLOCK, $this->pointer);

    }

    public function unmask() {

        if ( $this->pointer === null ) {
            return pcntl_sigprocmask(SIG_SETMASK, []);
        }

        return pcntl_sigprocmask(SIG_UNBLOCK, $this->pointer);

    }

}
