<?php

namespace Doody;

use MongoDB\BSON\Javascript;
use MongoDB\Model\BSONArray;
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

    private $client;

    public function __construct()
    {
        $this->client       = new Client();
        $this->db           = $this->client->selectDatabase(self::DB_NAME);
    }

    public function calculate(float $threshold)
    {
        echo 'Prepare initial Graph' . PHP_EOL;
        $total_nodes = $this->prepare();
        echo 'Initial Graph with ' . $total_nodes . ' Nodes constructed' . PHP_EOL;
        $i    = 1;
        $diff = 0;
        do {
            $this->iteration($i);
            $coll = $this->client->selectCollection(self::DB_NAME, self::PR_COLLECTION . $i);
            $diff = $coll->aggregate(
                [
                    [
                        '$group' => ['_id' => 1, 'total_diff' => ['$sum' => '$value.diff']]
                    ]
                ]
            )->toArray()[0]['total_diff'];
            echo 'Iteration: ' . $i . PHP_EOL;
            echo 'Current average difference: ' . ($diff / $total_nodes) . PHP_EOL;
            $i++;
        } while ($diff / $total_nodes > $threshold);
    }

    /**
     * Given the pages collection. Prepare constructs an initial collection
     * which is used to perform a pagerank calculation.
     *
     * @return The total size of the Graph(Nodecount)
     */
    private function prepare()
    {
        $initial_coll = self::PR_COLLECTION . 0;
        $this->db->dropCollection($initial_coll);
        $this->db->createCollection($initial_coll);

        $pages_coll = $this->client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        $pr_coll    = $this->client->selectCollection(self::DB_NAME, $initial_coll);

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
                    'value'     =>
                    [
                        'url'        => $page['_id'],
                        'pr'         => 1 / $count,
                        'out'        => [],
                        'out_count'  => 0,
                        'diff'       => 0,
                        'prev_pr'    => 0,
                    ],
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
                        '_id'     => '$in',
                        'count'   => ['$sum' => 1],
                        'out'     => ['$addToSet' => '$_id'],
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
                        'value.out'           => $ps,
                        'value.out_count'     => $out['count'],
                    ],
                ]
            );
        }

        $total = $pr_coll->aggregate(
            [
                [
                    '$group' => ['_id' => '$_id'],
                ],
                [
                    '$group' => ['_id' => 1, 'total' => ['$sum' => 1]],
                ],
            ]
        )->toArray()[0]['total'];

        $pr_coll->updateMany([], ['$set' => ['value.total' => $total]]);
        
        return $total;
    }

    private function iteration(int $i)
    {
        $cursor   = $this->db->command(
            [
                'mapReduce' => self::PR_COLLECTION . ($i - 1),
                'map'       => new Javascript(file_get_contents('src/js/map.js')),
                'reduce'    => new Javascript(file_get_contents('src/js/reduce.js')),
                'out'       => self::PR_COLLECTION . $i,
            ]
        );
    }

    private function mergeValueIntoArray($array, $value)
    {
        $result = [];
        foreach ($array as $entry) {
            $result[] = $entry->__toString();
        }

        return $result;
    }

    private function filterSelfLinks(string $url, BSONArray $ins)
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
