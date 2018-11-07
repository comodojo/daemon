Installation
============

.. highlight:: php

.. _install composer: https://getcomposer.org/doc/00-intro.md

First `install composer`_, then:

.. code:: bash

    composer require comodojo/daemon

Requirements
************

To work properly, comodojo/daemon requires PHP >=5.6.0.

Following PHP extension are also required:

- ext-posix: PHP interface to \*nix Process Control Extensions
- ext-pcntl: process Control support in PHP
- ext-shmop: read, write, create and delete Unix shared memory segments
- ext-sockets: low-level interface to the socket communication functions
