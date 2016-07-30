<?php

namespace Doody;

use MongoDB\BSON\Javascript;
use MongoDB\Client;

class PageRank
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';
    const PR_COLLECTION = 'pr_iteration_0';

    public function prepare()
    {
        $client = new Client();
        $db     = $client->selectDatabase(self::DB_NAME);
        $db->dropCollection(self::PR_COLLECTION);
        $db->createCollection(self::PR_COLLECTION);

        $pages_coll = $client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        $pr_coll = $client->selectCollection(self::DB_NAME, self::PR_COLLECTION);

        $pages = $pages_coll->aggregate(
            [
                [
                    '$group' => ['_id' => '$base']
                ]
            ]
        );
        $count = $pages_coll->aggregate(
            [
                [
                    '$group' => ['_id' => '$base'],
                ],
                [
                    '$group' => ['_id' => 'count', 'count' => ['$sum' => 1]]
                ]
            ]
        )->toArray()[0]['count'];

        foreach ($pages as $page) {
            $pr_coll->insertOne(
                [
                    '_id' => $page['_id'],
                    'pr' => 1 / $count,
                ]
            );
        }
    }

    public function calculate()
    {
        $database = (new Client())->selectDatabase('mongodb');
        $cursor   = $database->command(
            [
                'mapReduce' => 'pages',
                'map'       => new Javascript(file_get_contents('src/js/map.js')),
                'reduce'    => new Javascript(file_get_contents('src/js/reduce.js')),
                'out'       => 'pr_out',
            ]
        );

        $resultDocuments = $cursor->toArray();

        return $resultDocuments;
    }
}
