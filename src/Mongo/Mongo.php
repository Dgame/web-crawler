<?php

namespace Doody\Crawler\Mongo;

use Doody\Crawler\Url\Relation;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Class Mongo
 *
 * @package Doody\Crawler\Mongo
 */
final class Mongo
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @var \MongoDB\Collection
     */
    private $collection = null;

    /**
     * @var \MongoDB\Client
     */
    private $client;

    /**
     * @var \MongoDB\Database
     */
    private $db;

    /**
     * Mongo constructor.
     */
    private function __construct()
    {
        $this->client     = new Client();
        $this->db         = $this->client->selectDatabase(self::DB_NAME);
        if ($this->collExists()) {
            $this->collection = $this->client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        } else {
            $this->createColl();
        }
    }

    /**
     * @return Mongo
     */
    public static function Instance() : Mongo
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function createColl()
    {
        $this->db->createCollection(self::DB_COLLECTION);
        $this->collection = $this->client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        $this->collection->createIndex(
            ['content' => 'text'],
            ['default_language' => 'en']
        );
    }

    public function collExists()
    {
        $cursor = $this->db->listCollections();
        foreach ($cursor as $coll) {
            if ($coll->getName() === self::DB_COLLECTION) {
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
     * @param Relation $relation
     * @param string   $content
     * @param string   $lang
     *
     * @return bool
     */
    public function insert(Relation $relation, string $content, string $title, string $lang) : bool
    {
        $result = $this->collection->updateOne(
            [
                'url' => $relation->getChild()->asString(),
            ],
            [
                '$set'      => [
                    'url'      => $relation->getChild()->asString(),
                    'base'     => $relation->getChild()->getBaseUrl(),
                    'content'  => $content,
                    'title'    => $title,
                    'language' => $lang,
                    'pr'       => 0,
                ],
                '$addToSet' => [
                    'in' => $relation->getParent()->getBaseUrl(),
                ],
            ],
            [
                'upsert' => true,
            ]
        );

        return $result->isAcknowledged();
    }
}
