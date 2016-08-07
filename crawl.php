<?php

const LOG         = false;
const VERBOSE_LOG = false;
const SHOW_ERRORS = false;
const DB_INSERT   = true;

use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Crawler\Crawler;

require_once 'vendor/autoload.php';

list(, $url) = $argv;

if (!LOG) {
    FileLogger::Instance()->disable();
}

try {
    $crawler = new Crawler($url);

    print implode(PHP_EOL, $crawler->getLinks());
} catch (Throwable $t) {
    if (SHOW_ERRORS) {
        print $t;
    }
}
