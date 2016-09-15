<?php

const LOG           = false;
const VERBOSE_LOG   = false;
const SHOW_ERRORS   = true;
const DB_INSERT     = true;
const DB_BULK_LIMIT = 1000;

use Doody\Crawler\Crawler\DataRecorder;
use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Crawler\Crawler;

require_once 'vendor/autoload.php';

function shutdown(Crawler $crawler)
{
    file_put_contents(dirname(__FILE__) . '/rust/shutdown.txt', implode(PHP_EOL, $crawler->getLinks()), FILE_APPEND);
}

list(, $url) = $argv;

if (!LOG) {
    FileLogger::Instance()->disable();
}

try {
    $crawler = new Crawler($url);

    DataRecorder::Instance()->apply();

    //    register_shutdown_function('shutdown', $crawler);

    print implode(PHP_EOL, $crawler->getLinks());
} catch (Throwable $t) {
    if (SHOW_ERRORS) {
        print $t;
    }
}
