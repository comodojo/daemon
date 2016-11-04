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
