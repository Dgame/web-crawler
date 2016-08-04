<?php

use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Scanner\Scanner;

require_once 'vendor/autoload.php';

list(, $url) = $argv;

//FileLogger::Instance()->disable();

$scanner = new Scanner($url);

print implode(PHP_EOL, $scanner->getLinks());


