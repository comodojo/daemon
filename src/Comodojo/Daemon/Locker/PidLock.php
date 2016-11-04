<?php namespace Comodojo\Daemon\Locker;

use \Exception;

/**
 * Lock file manager (static methods)
 *
 * @package     Comodojo extender
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
 * @license     GPL-3.0+
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class PidLock extends AbstractLocker {

    /**
     * Lock file name
     *
     * @var string
     */
    private $lockfile = "daemon.pid";

    public function __construct($lockfile = null) {

        if ( $lockfile !== null ) $this->lockfile = $lockfile;

        // if ( empty($pid) ) throw new Exception("Invalid pid reference");

        // $this->pid = $pid;

    }

    public function lock($pid) {

        return self::writeLock($this->lockfile, $pid);

    }

    public function release() {

        return self::releaseLock($this->lockfile);

    }

}
