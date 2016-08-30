<?php

namespace Doody\Crawler\Mongo;

use Doody\Crawler\Crawler\Filter;
use Doody\Crawler\Language\Language;
use Doody\Crawler\Url\Relation;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Class Mongo.
 */
final class Mongo
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';

    /**
     * @var Mongo
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
        $this->client = new Client();
        $this->db     = $this->client->selectDatabase(self::DB_NAME);
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

    /**
     *
     */
    private function createColl()
    {
        $this->db->createCollection(self::DB_COLLECTION);
        $this->collection = $this->client->selectCollection(self::DB_NAME, self::DB_COLLECTION);
        $this->collection->createIndex(
            //['_id'              => 'hashed'], TODO: Verursacht Fehler. Nach dem Vortrag nochmal angucken
            ['content'          => 'text'],
            ['default_language' => 'en']
        );
    }

    /**
     * @return bool
     */
    private function collExists() : bool
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
     * @param Filter   $filter
     *
     * @return bool
     */
    public function insert(Relation $relation, Filter $filter) : bool
    {
        $title  = $filter->hasTitle() ? $filter->getTitle() : $relation->getChild()->getBaseUrl();
        $result = $this->collection->updateOne(
            [
                'url' => $relation->getChild()->asString(),
            ],
            [
                '$set' => [
                    'url'      => $relation->getChild()->asString(),
                    'base'     => $relation->getChild()->getBaseUrl(),
                    'content'  => $filter->getContent(),
                    'title'    => $title,
                    'language' => Language::Instance()->detectLanguage($filter->getContent()),
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
