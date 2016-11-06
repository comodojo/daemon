<?php namespace Comodojo\Daemon\Console;

use \Monolog\Logger;
use \Monolog\Handler\AbstractProcessingHandler;
use \League\CLImate\CLImate;

/**
 * Log-to-console handler
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

class LogHandler extends AbstractProcessingHandler {

    private $outputcontroller = null;

    private static $colors = array(
        100 => 'light_green',
        200 => 'green',
        250 => 'light_yellow',
        300 => 'yellow',
        400 => 'light_red',
        500 => 'red',
        550 => 'light_magenta',
        600 => 'magenta',
    );

    public function __construct($level = Logger::DEBUG, $bubble = true) {

        $this->outputcontroller = new CLImate();

        parent::__construct($level, $bubble);

    }

    protected function write(array $record) {

        $level = $record['level'];

        $message = $record['formatted'];

        $context = empty($record['context']) ? null : $record['context'];

        $time = $record['datetime']->format('c');

        $this->toConsole($time, $level, $message, $context);

    }

    private function toConsole($time, $level, $message, $context) {

        $color = static::$colors[$level];

        $pattern = "<%s>%s</%s>";

        $message = sprintf($pattern, $color, $message, $color);

        $this->outputcontroller->out($message);

        //if ( !empty($context) ) $this->outputcontroller->out(sprintf($pattern, $color, var_export($context, true), $color));

    }

}
