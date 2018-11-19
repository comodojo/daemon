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
        // Noone can catch SIGKILL!
        // SIGKILL => 'SIGKILL',
        SIGUSR1 => 'SIGUSR1',
        SIGSEGV => 'SIGSEGV',
        SIGUSR2 => 'SIGUSR2',
        SIGPIPE => 'SIGPIPE',
        SIGALRM => 'SIGALRM',
        SIGTERM => 'SIGTERM',
        SIGCHLD => 'SIGCHLD',
        SIGCONT => 'SIGCONT',
        SIGTSTP => 'SIGTSTP',
        SIGTTIN => 'SIGTTIN',
        SIGTTOU => 'SIGTTOU',
        SIGURG => 'SIGURG',
        SIGXCPU => 'SIGXCPU',
        SIGXFSZ => 'SIGXFSZ',
        SIGVTALRM => 'SIGVTALRM',
        SIGPROF => 'SIGPROF',
        SIGWINCH => 'SIGWINCH',
        SIGSYS => 'SIGSYS',
        SIGBABY => 'SIGBABY'
    ];

    protected $pointer = [];

    public function __construct() {

        if ( defined('SIGPOLL') ) $this->data[SIGPOLL] = 'SIGPOLL';
        if ( defined('SIGPWR') ) $this->data[SIGPWR] = 'SIGPWR';
        if ( defined('SIGSTKFLT') ) $this->data[SIGSTKFLT] = 'SIGSTKFLT';
        // if ( defined('SIGSTOP') ) $this->data[SIGSTOP] = 'SIGSTOP';
        if ( defined('SIGIO') ) $this->data[SIGIO] = 'SIGIO';
        if ( defined('SIGCLD') ) $this->data[SIGCLD] = 'SIGCLD';

    }

    public function sigNo($signame) {

        $reverse_signals = array_flip($this->data);

        return array_key_exists($signame, $reverse_signals) ? $reverse_signals[$signame] : null;

    }

    public function sigName($signo) {

        return array_key_exists($signo, $this->data) ? $this->data[$signo] : null;

    }

    public function on(...$signals) {

        foreach ( $signals as $signal ) {
            if ( !isset($this[$signal]) ) throw new Exception("Signal $signal not supported");
        }

        $this->pointer = $signals;

        return $this;

    }

    public function any() {

        $this->pointer = [];

        return $this;

    }

    public function call($callable) {

        if ( empty($this->pointer) ) {
            $result = [];
            foreach ( $this as $signo => $signame ) {
                $result[] = $re = pcntl_signal($signo, $callable);
                if ( !$re ) echo "\n>>>Signal $signo (".$this->sigName($signo).") failed\n";
            }
            return !in_array(false, $result);
        }

        return array_map(function($signal) {
            return pcntl_signal($signal, $callable);
        }, $this->pointer);

    }

    public function setDefault() {

        if ( empty($this->pointer) ) {
            $result = [];
            foreach ( $this as $signo => $signame ) {
                $result[] = pcntl_signal($signo, SIG_DFL);
            }
            return !in_array(false, $result);
        }

        return array_map(function($signal) {
            return pcntl_signal($signal, SIG_DFL);
        }, $this->pointer);

    }

    public function mask() {

        if ( empty($this->pointer) ) {
            return pcntl_sigprocmask(SIG_SETMASK, (array) $this);
        }

        return pcntl_sigprocmask(SIG_BLOCK, $this->pointer);

    }

    public function unmask() {

        if ( empty($this->pointer) ) {
            return pcntl_sigprocmask(SIG_SETMASK, []);
        }

        return pcntl_sigprocmask(SIG_UNBLOCK, $this->pointer);

    }

}
