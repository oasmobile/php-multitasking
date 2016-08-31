# php-multitasking
Multitasking support for PHP, using pcntl library to fork children processes

## Installation

oasis/multitasking is an open-source component available at `packagist.org`. To require the package, try the following in your project directory:

```bash
composer require oasis/multitasking
```

## Background Worker Manager

If you ever want to run something in the background(i.e. a `callable` that is often referred to as _worker_), you should have a look at `BackgroundWorkerManager` class. This class provides the following features:

- Run multiple workers in the background (forked process)
- An ordered worker queue which limits the concurrent running worker to a preset number
- Wait functionality for the parent process

Example:
```php
<?php

$man = new BackgroundWorkerManager(2);
$man->addWorker(
    function (WorkerInfo $info) {
        echo sprintf(
            "This is the #%d worker executed out of a total of %d",
            $info->currentWorkerIndex,
            $info->totalWorkers
            );
    },
    10
    ); // add 10 workers to the manager

$man->run(); // all workers will be executed in order, no more than 2 workes will be running at the same time

$man->wait();
```
