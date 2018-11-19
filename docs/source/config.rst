.. _configuration:

Daemon configuration
====================

.. _PHP socket documentation: http://php.net/manual/en/book.sockets.php
.. _nice unix command on wikipedia: https://en.wikipedia.org/wiki/Nice_(Unix)
.. _climate documentation: https://climate.thephpleague.com/

A daemon created using this package can be configured using an array of parameters provided as the first input argument to the ``\Comodojo\Daemon\Daemon`` abstract class. As an example:

.. code-block:: php
    :linenos:

    #!/usr/bin/env php
    <?php

    use \DaemonExamples\EchoDaemon;

    $configuration = [
        'description' => 'Echo Daemon',
        'sockethandler' => 'tcp://127.0.0.1:10042'
    ];

    // Create a new instance of EchoDaemon
    $daemon = new EchoDaemon($configuration);

.. note:: This code is available in the `daemon-examples github repository`_.

Configuration parameters
------------------------

Following a list of accepted configuration parameters.

sockethandler
.............

Address and type of the socket handler (see the `PHP socket documentation`_).

*Example*: 'sockethandler' => 'tcp://127.0.0.1:60001'

*Default*: 'sockethandler' => 'unix://daemon.sock'

pidfile
.......

Location (relative to the base path) of the daemon's pid file.

*Default*: 'pidfile' => 'daemon.pid'

.. note:: Prepend a slash to the file loaction to make it absolute (e.g. /tmp/daemon.pid).

socketbuffer
............

Size of the socket buffer (see the `PHP socket documentation`_).

*Default*: 'socketbuffer' => 1024

sockettimeout
.............

Timeout for the select() system call (see the `PHP socket documentation`_).

*Default*: 'sockettimeout' => 2

socketmaxconnections
....................

Maximum number of connection accepted by the socket.

*Default*: 'socketmaxconnections' => 10

niceness
........

Define the nice value of the daemon process (see the `nice unix command on wikipedia`_).

*Default*: 'niceness' => 0

arguments
.........

Definition of command line arguments, in the climate format (see `climate documentation`_).

*Default*: 'arguments' => '\\Comodojo\\Daemon\\Console\\DaemonArguments'

description
...........

Description banner in the daemon command line.

*Default*: 'description' => 'Comodojo Daemon'