<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2017-01-11
 * Time: 18:49
 */

use Oasis\Mlib\Multitasking\MessageQueue;

require_once __DIR__ . "/vendor/autoload.php";

$msgq = new MessageQueue("xuchang");

//$msgq->send("hello");

$ret = $msgq->receive($msg, $type, 0, false);
var_dump($msg);
var_dump($ret);

