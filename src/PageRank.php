<?php

namespace Doody;

use MongoDB\BSON\Javascript;
use MongoDB\Client;

class PageRank
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';
    const PR_COLLECTION = 'pr_iteration_';

    /**
     * Database Instance where the collections will be placed
     */
    private $db;

    public function __construct()
    {
        $this->db = (new Client())->selectDatabase(self::DB_NAME);
    }

    public function calculate(int $iterations)
    {
        $this->prepare();
        for ($i = 0; $i < $iterations; $i++) {
            $this->iteration($i);
        }
    }

    /**
     * Given the pages collection. Prepare constructs an initial collection 
     * which is used to perform a pagerank calculation. 
     */
    private function prepare()
    {
        $client = new Client();
        $initial_coll = self::PR_COLLECTION . 0;
        $this->db->dropCollection($initial_coll);
        $this->db->createCollection($initial_coll);

        $pages_coll = $client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        $pr_coll    = $client->selectCollection(self::DB_NAME, $initial_coll);

        $pages = $pages_coll->aggregate(
            [
                [
                    '$unwind' => '$in',
                ],
                [
                    '$group' => [
                        '_id' => '$base',
                        'in'  => ['$addToSet' => '$in'],
                    ],
                    
                ],
            ]
        );
        $count = $pages_coll->aggregate(
            [
                [
                    '$group' => ['_id' => '$base'],
                ],
                [
                    '$group' => ['_id' => 'count', 'count' => ['$sum' => 1]],
                ],
            ]
        )->toArray()[0]['count'];

        foreach ($pages as $page) {
            $page['in'] = $this->filterSelfLinks($page['_id'], $page['in']);
            $pr_coll->insertOne(
                [
                    'in'        => $page['in'],
                    'value' =>
                    [
                        'url'       => $page['_id'],
                        'pr'        => 1 / $count,
                        'ps'        => [],
                    ]
                ]
            );
        }

        $outs = $pr_coll->aggregate(
            [
                [
                    '$unwind' => '$in',
                ],
                [
                    '$group' =>
                    [
                        '_id'    => '$in',
                        'count'  => ['$sum' => 1],
                        'out'    => ['$addToSet' => '$_id'],
                    ],
                ],
            ]
        );

        foreach ($outs as $out) {
            $ps = $this->mergeValueIntoArray($out['out'], 1 / $out['count']);
            $pr_coll->updateOne(
                [
                    'value.url' => $out['_id'],
                ],
                [
                    '$set' => [
                        'value.ps'        => $ps,
                    ],
                    '$unset' => [
                        'in' => '',
                    ],
                ]
            );
        }

        //$pr_coll->deleteMany(
            //[
                //'value.ps' => [],
            //]
        //);


        $count = $pr_coll->aggregate(
            [
                [
                    '$group' => ['_id' => '$_id'],
                ],
                [
                    '$group' => ['_id' => 'count', 'count' => ['$sum' => 1]],
                ],
            ]
        )->toArray()[0]['count'];

        $pr_coll->updateMany([],['$set' => ['value.total' => $count]]);
    }

    private function iteration(int $i)
    {
        $cursor   = $this->db->command(
            [
                'mapReduce' => self::PR_COLLECTION . $i,
                'map'       => new Javascript(file_get_contents('src/js/map.js')),
                'reduce'    => new Javascript(file_get_contents('src/js/reduce.js')),
                'out'       => self::PR_COLLECTION . ($i + 1),
            ]
        );
    }

    private function mergeValueIntoArray($array, $value)
    {
        $result = [];
        foreach ($array as $entry) {
            $result[$entry->__toString()] = $value;
        }

        return $result;
    }

    private function filterSelfLinks(string $url, \MongoDB\Model\BSONArray $ins)
    {
        $array = $ins->getArrayCopy();

        $filtered = array_filter($array, function ($entry) use ($url) {
            return $url !== $entry;
        });

        $index  = 0;
        $result = [];
        foreach ($filtered as $entry) {
            $result[] = $entry;
        }

        return $result;
    }
}
