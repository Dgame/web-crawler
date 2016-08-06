<?php

namespace Doody\Crawler\Pagerank;

use MongoDB\BSON\Javascript;
use MongoDB\Model\BSONArray;
use MongoDB\Client;

class Pagerank
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';
    const PR_COLLECTION = 'pr_iteration_';

    /**
     * Database Instance where the collections will be placed
     */
    private $db;

    /**
     * The MongoDB Client Instance
     */
    private $client;

    /**
     * Construct an Instance of the Pagerank CalculatorCalculator. Initialises 
     * the client connection and the default database
     */
    public function __construct()
    {
        $this->client       = new Client();
        $this->db           = $this->client->selectDatabase(self::DB_NAME);
    }

    /**
     * Calculate the Pagerank. First an initial graph is created. Afterwards the
     * iterative PageRank is applied until the average difference in Pagerank
     * values is below the given threshold
     * @param float $threshold The threshold, which causes, if below, the 
     * abortion of the pagerank algorithm
     */
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
            echo 'Current total difference: ' . $diff . PHP_EOL;
            echo 'Current average difference: ' . ($diff / $total_nodes) . PHP_EOL;
            $i++;
        } while ($diff / $total_nodes > $threshold);
    }

    /**
     * Given the pages collection. Prepare constructs an initial collection
     * which is used to perform a pagerank calculation.
     *
     * @return The total size of the Graph(Node cardinality)
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
                        'pr'         => (1 / $count) * 100,
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
            $ps = $this->mergeValueIntoArray($out['out']);
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

    /**
     * Performs a Pagerank iteration on a given initial graph $i - 1. Calls a 
     * MapReduce function, which code can be found in the js/ subfolder.
     * @param int $i the current iteration. Creates a Collection, which ends on
     * this index.
     */
    private function iteration(int $i)
    {
        $cursor   = $this->db->command(
            [
                'mapReduce' => self::PR_COLLECTION . ($i - 1),
                'map'       => new Javascript(file_get_contents(dirname(__FILE__) . '/js/map.js')),
                'reduce'    => new Javascript(file_get_contents(dirname(__FILE__) . '/js/reduce.js')),
                'out'       => self::PR_COLLECTION . $i,
            ]
        );
    }

    private function mergeValueIntoArray($array)
    {
        $result = [];
        foreach ($array as $entry) {
            $result[] = $entry->__toString();
        }

        return $result;
    }

    /**
     * Given a URL and a BSONArray, the function filters all entries which are
     * the same as $url. Also rearranges the indeces to from any arbitrary order
     * to a 0..n range(This is a PHP thing)
     * @param string $url the url against which the array is checked
     * @param string $ins the array, which will be filtered
     *
     * @return a filtered php array with (1..n) indeces
     */
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
