<?php

use Doody\PageRank;

require_once 'vendor/autoload.php';

$pr = new PageRank();
$pr->calculate(5);
