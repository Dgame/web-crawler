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
        $db->createCollection(self::PR_COLLECTION);
        $pr_coll = $client->selectCollection(self::DB_NAME, self::PR_COLLECTION);
        $pages   = $client->selectCollection(self::DB_NAME, self::DB_COLLECTION);

        $cursor = $db->command(
            [
                'mapReduce' => 'pages',
                'map'       => new Javascript(file_get_contents('src/prep_map.js')),
                'reduce'    => new Javascript(file_get_contents('src/prep_reduce.js')),
                'out'       => self::PR_COLLECTION,
            ]
        );
    }

    public function calculate()
    {
        $database = (new Client())->selectDatabase('mongodb');
        $cursor   = $database->command(
            [
                'mapReduce' => 'pages',
                'map'       => new Javascript(file_get_contents('src/map.js')),
                'reduce'    => new Javascript(file_get_contents('src/reduce.js')),
                'out'       => 'pr_out',
            ]
        );

        $resultDocuments = $cursor->toArray();

        return $resultDocuments;
    }
}
