<?php

use Doody\Crawler\Pagerank\Pagerank;

require_once 'vendor/autoload.php';

$pr = new Pagerank();
$pr->calculate(0.00000001);
