<?php

use \Comodojo\Daemon\Tests\Mock\Process;
use \Comodojo\Daemon\Tests\Mock\Listeners\ReactOnSignal;
use \Comodojo\Daemon\Utils\ProcessTools;

class ProcessTest extends \PHPUnit_Framework_TestCase {

    protected $process;

    public function setUp() {

        $this->process = new Process();

    }

    public function testProcessPid() {

        $mypid = ProcessTools::getPid();

        $this->assertSame($mypid, $this->process->getPid());

    }

}
