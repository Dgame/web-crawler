<?php

require_once 'vendor/autoload.php';

use Doody\Crawler\Mongo\MongoCollection;

$pages = MongoCollection::Instance('raw_pages')->getCollection()->aggregate(
    [
        [
            '$unwind' => '$in',
        ],
        [
            '$group' => [
                '_id'      => '$url',
                'url'      => ['$first' => '$url'],
                'base'     => ['$first' => '$base'],
                'content'  => ['$first' => '$content'],
                'title'    => ['$first' => '$title'],
                'language' => ['$first' => '$language'],
                'pr'       => ['$avg' => '$pr'],
                'in'       => ['$addToSet' => '$in'],
            ],
        ],
    ],
    [
        'allowDiskUse' => true
    ]
);

$bulk = [];
foreach ($pages as $page) {
    unset($page['_id']);

    $bulk[] = $page;
    if (count($bulk) % 1000 === 0) {
        MongoCollection::Instance('pages')->getCollection()->insertMany($bulk);
        $bulk = [];
    }
}

if (count($bulk) !== 0) {
    MongoCollection::Instance('pages')->getCollection()->insertMany($bulk);
    $bulk = [];
}

MongoCollection::Instance('raw_pages')->getCollection()->drop();