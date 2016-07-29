<?php

use Doody\Crawler\Crawler;

require_once 'vendor/autoload.php';

list(, $url) = $argv;

Crawler::Instance()->crawl($url);

print implode(PHP_EOL, Crawler::Instance()->getScannedLinks());

