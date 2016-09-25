<?php

namespace Doody\Crawler\Pagerank;

use MongoDB\BSON\Javascript;
use MongoDB\Client;
use MongoDB\Collection;

class Pagerank
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';
    const PR_COLLECTION = 'pr_iteration_';
    const BULK_SIZE     = 5000;

    /**
     * Database Instance where the collections will be placed
     */
    private $db;

    /**
     * The MongoDB Client Instance
     */
    private $client;

    /**
     * The pages Collection Cursor
     */
    private $pages_coll;

    /**
     * Construct an Instance of the Pagerank CalculatorCalculator. Initialises
     * the client connection and the default database
     */
    public function __construct()
    {
        $this->client     = new Client();
        $this->db         = $this->client->selectDatabase(self::DB_NAME);
        $this->pages_coll = $this->client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
    }

    /**
     * Calculate the Pagerank. First an initial graph is created. Afterwards the
     * iterative PageRank is applied until the average difference in Pagerank
     * values is below the given threshold
     *
     * @param float $threshold The threshold, which causes, if below, the
     *                         abortion of the pagerank algorithm
     */
    public function calculate(float $threshold)
    {
        echo 'Prepare initial Graph' . PHP_EOL;
        $this->prepare();
        echo 'Initial Graph with Nodes constructed' . PHP_EOL;
        $i    = 1;
        $diff = 0;
        do {
            $this->iteration($i);
            $coll = $this->client->selectCollection(self::DB_NAME, self::PR_COLLECTION . $i);
            $diff = $coll->aggregate(
                [
                    [
                        '$group' => ['_id' => 1, 'diff' => ['$avg' => '$value.diff']],
                    ],
                ]
            )->toArray()[0]['diff'];
            echo 'Iteration: ' . $i . PHP_EOL;
            echo 'Current average difference: ' . $diff . PHP_EOL;
            $i++;
        } while ($diff > $threshold);
        //Reset i to last iteration
        $i--;
        echo 'Pagerank calculation finished after ' . $i . ' iterations' . PHP_EOL;
        echo 'Begin to transfer pagerank values to pages collection' . PHP_EOL;
        $this->transfer($i);
        echo 'Transfer finished successfully' . PHP_EOL;
    }

    /**
     * Given the pages collection. Prepare constructs an initial collection
     * which is used to perform a pagerank calculation.
     */
    private function prepare()
    {
        $initial_coll = self::PR_COLLECTION . 0;
        $this->db->dropCollection($initial_coll);
        $this->db->createCollection($initial_coll);

        $pr_coll = $this->client->selectCollection(self::DB_NAME, $initial_coll);

        $bulk = [];
        //Aggregate by outgoing links and count them
        $outs = $this->pages_coll->aggregate(
            [
                [
                    '$unwind' => '$in',
                ],
                [
                    '$group' =>
                        [
                            '_id'   => '$in',
                            'count' => ['$sum' => 1],
                            'out'   => ['$addToSet' => '$_id'],
                        ],
                ],
            ]
        );

        foreach ($outs as $out) {
            $page = $this->pages_coll->findOne(['url' => $out['_id']]);
            if ($page !== null) {
                $bulk[] = [
                    '_id'   => $page['_id'],
                    'value' =>
                        [
                            'pr'        => 1,
                            'out'       => $this->mergeValueIntoArray($out['out']),
                            'out_count' => $out['count'],
                            'diff'      => 0,
                            'prev_pr'   => 0,
                        ],
                ];
                if (count($bulk) % self::BULK_SIZE === 0) {
                    $pr_coll->insertMany($bulk);
                    $bulk = [];
                }
            }
        }

        //Insert the rest
        if (count($bulk) > 0) {
            $pr_coll->insertMany($bulk);
            $bulk = [];
        }

        $pages = $this->pages_coll->find();
        foreach ($pages as $page) {
            if (!$pr_coll->findOne(['_id' => $page['_id']])) {
                $in       = $this->transformIn($page['in']);
                $count_in = count($in);
                if ($count_in > 0) {
                    $bulk[] = [
                        '_id'   => $page['_id'],
                        'value' =>
                            [
                                'pr'        => 1,
                                'out'       => $in,
                                'out_count' => $count_in,
                                'diff'      => 0,
                                'prev_pr'   => 0,
                            ],
                    ];

                    if (count($bulk) % self::BULK_SIZE === 0) {
                        $pr_coll->insertMany($bulk);
                        $bulk = [];
                    }
                }
            }
        }

        //Insert the rest
        if (count($bulk) > 0) {
            $pr_coll->insertMany($bulk);
        }
    }

    private function transformIn($ins)
    {
        $result = [];
        foreach ($ins as $in) {
            $page = $this->pages_coll->findOne(['url' => $in]);
            if ($page) {
                $result[] = $page['_id']->__toString();
            }
        }

        return $result;
    }

    /**
     * Performs a Pagerank iteration on a given initial graph $i - 1. Calls a
     * MapReduce function, which code can be found in the js/ subfolder.
     *
     * @param int $i the current iteration. Creates a Collection, which ends on
     *               this index.
     */
    private function iteration(int $i)
    {
        $cursor = $this->db->command(
            [
                'mapReduce' => self::PR_COLLECTION . ($i - 1),
                'map'       => new Javascript(file_get_contents(dirname(__FILE__) . '/js/map.js')),
                'reduce'    => new Javascript(file_get_contents(dirname(__FILE__) . '/js/reduce.js')),
                'out'       => self::PR_COLLECTION . $i,
            ]
        );
    }

    /**
     * Transfers the pagerank from the iterations collection to the pages
     * collection
     *
     * @param int $i the iteration from which collection the pageranks
     *               should get taken
     */
    public function transfer(int $i)
    {
        $pr_coll = $this->client->selectCollection(self::DB_NAME, self::PR_COLLECTION . $i);

        $docs  = $pr_coll->find();
        $total = $this->total($pr_coll, '$_id');
        foreach ($docs as $doc) {
            $this->pages_coll->updateOne(
                [
                    '_id' => $doc['_id'],
                ],
                [
                    '$set' => ['pr' => $doc['value']['pr'] / $total],
                ]
            );
        }
    }

    /**
     * Count all documents in a collection, by a given grouping
     *
     * @param        $collection the collecten where the aggregation will be processed
     * @param string $groupBy    the group identifier by which field the
     *                           aggregation should group the collection
     *
     * @return int the total number of documents after the group
     */
    private function total(Collection $collection, string $groupBy)
    {
        return $collection->aggregate(
            [
                [
                    '$group' => ['_id' => $groupBy],
                ],
                [
                    '$group' => ['_id' => 1, 'total' => ['$sum' => 1]],
                ],
            ]
        )->toArray()[0]['total'];
    }

    private function mergeValueIntoArray($array)
    {
        $result = [];
        foreach ($array as $entry) {
            $result[] = $entry->__toString();
        }

        return $result;
    }
}
