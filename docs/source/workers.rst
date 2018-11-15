Using Workers
=============

.. _daemon-examples github repository: https://github.com/marcogiovinazzi/daemon-examples

In comodojo/daemon workers are, essentially, child processes that run in parallel maintaining a communication channel with the master daemon. Each worker has its own loop that can be configured from the daemon.

Creating a worker
-----------------

The simplest way to create a worker, is to extend the ``\Comodojo\Daemon\Worker\AbstractWorker`` abstract class implementing the  ``loop()`` method.

There are two other optional methods, ``spinup()`` and ``spindown`` that can be used to control the worker startup and execute action before shutting down.

As an example, let's consider the following *CopyWorker*: it's job is to check if a specific *test.txt" file exists in the *tmp* directory and, if it's there, duplicate the file.

.. code-block:: php
    :linenos:

    <?php namespace DaemonExamples;

    use \Comodojo\Daemon\Worker\AbstractWorker;

    class CopyWorker extends AbstractWorker {

        protected $path;

        // Source file
        protected $file = 'test.txt';
        
        // Destination file
        protected $copy = 'copy_test.txt';
        
        public function spinup() {

            $this->logger->info("CopyWorker ".$this->getName()." spinning up...");
            $this->path = realpath(dirname(__FILE__)."/../../tmp/");

        }

        public function loop() {
            
            $filename = $this->path."/".$this->file;

            if ( file_exists($filename) ) {
                copy($filename, $this->path."/".$this->copy);
            }

        }

        public function spindown() {

            $this->logger->info("CopyWorker ".$this->getName()." spinning down.");
            unlink($this->path."/".$this->copy);

        }

    }

.. note:: This code is available in the `daemon-examples github repository`_.

Adding a worker to the daemon
-----------------------------

In order to run, a worker should be installed in the daemon before calling the ``init()`` method. The internal workers stack ``Comodojo\Daemon\Worker\Manager`` can be accessed using the ``$daemon::getWorkers()`` getter.

The ``install()`` method can be used to push a worker into the stack, specifying the looptime:

.. code-block:: php
    :linenos:

    #!/usr/bin/env php
    <?php

    $base_path = realpath(dirname(__FILE__)."/../");
    require "$base_path/vendor/autoload.php";

    use \DaemonExamples\CopyDaemon;
    use \DaemonExamples\CopyWorker;

    $configuration = [
        'description' => 'Copy Daemon',
        'sockethandler' => 'tcp://127.0.0.1:10042'
    ];

    $daemon = new CopyDaemon($configuration);

    // Create a CopyWorker with name: handyman
    $handyman = new CopyWorker("handyman");

    // Install the worker into the stack configuring a 10 secs looptime and enabling the forever watchdog
    $daemon->getWorkers()->install($handyman, 10, true);

    $daemon->init();

.. note:: This code is available in the `daemon-examples github repository`_.

The forever switch
------------------

The ``install()`` method allows also to enable the *forever* mode for the worker. When the third argument is set to *true*, the internal watchdog of the daemon will restart the worker in case of crash, with no need to restart the whole daemon. On the contrary, in case of *false* a controlled shutdown of the whole daemon will be triggered if one worker goes down.

Communicating with the worker
-----------------------------

TBW