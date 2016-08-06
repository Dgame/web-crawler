<?php

const DEBUG       = false;
const VERBOSE_LOG = false;

use Doody\Crawler\Crawler\Crawler;
use Doody\Crawler\Http\HttpDispatcher;
use Doody\Crawler\Logger\FileLogger;

require_once 'vendor/autoload.php';

list(, $url_list) = $argv;

if (!DEBUG) {
    FileLogger::Instance()->disable();
}

try {
    $links = [];

    $dispatcher = new HttpDispatcher();
    $dispatcher->dispatch(
        array_map('trim', explode(',', $url_list)),
        function (array $info, string $content) use (&$links) {
            $crawler = new Crawler($info['url'], $content);
            $links   = array_merge($links, $crawler->getLinks());
        }
    );

    print implode(PHP_EOL, $links);
} catch (Throwable $t) {
    if (DEBUG) {
        print $t;
    }
}
