<?php

namespace Doody\Crawler\Mongo;

use Doody\Crawler\Url\Relation;
use MongoDB\Client;
use MongoDB\Collection;

/**
 * Class Mongo
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
     * Mongo constructor.
     */
    private function __construct()
    {
        $this->collection = (new Client())->selectCollection(self::DB_NAME, self::DB_COLLECTION);
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
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param Relation $relation
     * @param string   $content
     *
     * @return bool
     */
    public function insert(Relation $relation, string $content) : bool
    {
        $result = $this->collection->updateOne(
            [
                'url' => $relation->getChild()->asString()
            ],
            [
                '$set'      => [
                    'url'     => $relation->getChild()->asString(),
                    'base'    => $relation->getChild()->getBaseUrl(),
                    'content' => $content,
                    'pr'      => 0,
                ],
                '$addToSet' => [
                    'in' => $relation->getParent()->getBaseUrl()
                ]
            ],
            [
                'upsert' => true
            ]
        );

        return $result->isAcknowledged();
    }
}