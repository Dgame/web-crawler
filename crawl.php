<?php

const DEBUG       = false;
const VERBOSE_LOG = false;
const PING_URL    = false;
const DB_INSERT   = true;

use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Scanner\Scanner;

require_once 'vendor/autoload.php';

list(, $url) = $argv;

if (!DEBUG) {
    FileLogger::Instance()->disable();
}

try {
    $scanner = new Scanner($url);

    print implode(PHP_EOL, $scanner->getLinks());
} catch (Throwable $t) {
    if (DEBUG) {
        print $t;
    }
}
