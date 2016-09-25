<?php

namespace Doody\Crawler\Mongo;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

/**
 * Class MongoCollection
 * @package Doody\Crawler\Mongo
 */
final class MongoCollection
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';

    const INDICES = [
        self::DB_COLLECTION => [
            'key'     => ['content' => 'text'],
            'options' => ['default_language' => 'en']
        ]
    ];

    /**
     * @var MongoCollection[]
     */
    private static $instances = [];
    /**
     * @var \MongoDB\Collection
     */
    private $collection;
    /**
     * @var \MongoDB\Client
     */
    private $client;
    /**
     * @var \MongoDB\Database
     */
    private $db;

    /**
     * MongoCollection constructor.
     *
     * @param string $collection
     */
    private function __construct(string $collection)
    {
        $this->client = new Client();
        $this->db     = $this->client->selectDatabase(self::DB_NAME);
        if ($this->collectionExists($collection)) {
            $this->collection = $this->client->selectCollection(self::DB_NAME, $collection);
        } else {
            $this->createCollection($collection);
        }
    }

    /**
     * @param string|null $collection
     *
     * @return MongoCollection
     */
    public static function Instance(string $collection = null) : MongoCollection
    {
        $collection = $collection ?? self::DB_COLLECTION;
        if (!array_key_exists($collection, self::$instances)) {
            self::$instances[$collection] = new self($collection);
        }

        return self::$instances[$collection];
    }

    /**
     *
     */
    private function createCollection(string $collection)
    {
        $this->db->createCollection($collection);
        $this->collection = $this->client->selectCollection(self::DB_NAME, $collection);

        if (array_key_exists($collection, self::INDICES)) {
            $this->collection->createIndex(self::INDICES[$collection]['key'], self::INDICES[$collection]['options']);
        }
    }

    /**
     * @return bool
     */
    private function collectionExists(string $collection) : bool
    {
        $cursor = $this->db->listCollections();
        foreach ($cursor as $coll) {
            if ($coll->getName() === $collection) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @return Database
     */
    public function getDb(): Database
    {
        return $this->db;
    }
}
