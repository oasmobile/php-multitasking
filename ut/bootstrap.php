<?php
/**
 * Created by PhpStorm.
 * User: minhao
 * Date: 2016-08-30
 * Time: 15:18
 */

use Oasis\Mlib\Logging\LocalFileHandler;

require_once __DIR__ . "/../vendor/autoload.php";

(new LocalFileHandler())->install();

