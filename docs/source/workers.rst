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

When a worker is created, the daemon will open a bidirectional communication channel using standard Unix shared memory segments. This channel will be kept opened for the entire life of the process.

Using this channel:

1. the daemon is able to pool the worker to konw its state (running, paused, ...) and trigger actions if the daemon crashes (worker watchdog);
2. the user can send commands to the worker using the daemon RPC socket.

While the first point is totally automated, the second one requires a user interaction.

Using default commands
......................

By default, the RPC socket expose a couple of method to manage workers:

1. ``worker.list()`` - get the list of the currently installed workers
2. ``worker.status(worker_name)`` - get the status of the worker
    - 0 => SPINUP
    - 1 => LOOPING
    - 2 => PAUSED
    - 3 => SPINDOWN
3. ``worker.pause(worker_name*)`` - pause the worker
4. ``worker.resume(worker_name*)`` - resume the worker

These commands are automatically sent to the communication channel (using shmop), trapped by the worker loop and then propagated as ``\Comodojo\Daemon\Events\WorkerEvent``. A listener on the worker side is responsible for executing the related action.

For example, this RPC request can be used to request the status of all workers:

.. code-block:: php
    :linenos:

    $request = \Comodojo\RpcClient\RpcRequest::create("worker.status", []);

And the following one to pause the *handyman* worker:

.. code-block:: php
    :linenos:

    $request = RpcRequest::create("worker.pause", ["handyman"]);

Defining custom actions
.......................

Custom actions can be defined in the worker to trap user defined commands, using the same mechanism described in the previous section.

As an example, let's customize the CopyDaemon/CopyWorker to change the output filename if a *handyman.changename* request is received.

To create this custom action, first step is to create a custom listener to handle the WorkerEvent:

.. code-block:: php
    :linenos:

    <?php namespace DaemonExamples;

    use \League\Event\AbstractListener;
    use \League\Event\EventInterface;
    
    class ChangeNameListener extends AbstractListener {

        public function handle(EventInterface $event) {

            // get the current worker instance
            $worker = $event->getWorker()->getInstance();

            // invoke the changeName method
            $worker->changeName();

            return true;

        }

    }

This listener should be hooked to a custom event at the worker level. The modified version of the CopyWorker is:

.. code-block:: php
    :linenos:

    <?php namespace DaemonExamples;

    use \Comodojo\Daemon\Worker\AbstractWorker;

    class CopyWorker extends AbstractWorker {

        protected $path;

        protected $file = 'test.txt';
        
        protected $copy = 'copy_test.txt';
        
        public function spinup() {

            $this->logger->info("CopyWorker ".$this->getName()." spinning up...");
            $this->path = realpath(dirname(__FILE__)."/../../tmp/");

            // Hook on daemon.worker.changename event to change the output file name
            $this->getEvents()
                ->subscribe('daemon.worker.changename', '\DaemonExamples\ChangeNameListener');

        }

        public function loop() {
            
            $filename = $this->path."/".$this->file;

            if ( file_exists($filename) ) {
                $this->logger->info("Copying file ".$this->file." to ".$this->copy);
                copy($filename, $this->path."/".$this->copy);
            }

        }

        public function spindown() {

            $this->logger->info("CopyWorker ".$this->getName()." spinning down.");
            unlink($this->path."/".$this->copy);

        }

        // this method will be invoked by the listener for daemon.worker.changename event
        public function changeName() {
            $this->logger->info("Changing filename...");
            $this->copy = 'copy_test_2.txt';

        }

    }

.. note:: This code is available in the `daemon-examples github repository`_.

The last step is to create a custom RPC Method in the daemon that can handle the *handyman.changename* request translating it to a message *changename* propagated in the communication channel (output side):

.. code-block:: php
    :linenos:

    <?php namespace DaemonExamples;

    use \Comodojo\Daemon\Daemon as AbstractDaemon;
    use \Comodojo\RpcServer\RpcMethod;

    class CopyDaemon extends AbstractDaemon {

        public function setup() {

            // define the changename method using lambda function
            $change = RpcMethod::create("handyman.changename", function($params, $daemon) {
                return $daemon->getWorkers()
                ->get("handyman")
                ->getOutputChannel()
                ->send('changename') > 0;
            }, $this)
                ->setDescription("Change the output file name")
                ->setReturnType('string');

            // inject the method to the daemon internal RPC server
            $this->getSocket()
                ->getRpcServer()
                ->methods()
                ->add($change);
                
        }

    }

.. note:: This code is available in the `daemon-examples github repository`_.