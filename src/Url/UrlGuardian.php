<?php

namespace Doody\Crawler\Url;

/**
 * Class UrlGuardian
 * @package Doody\Crawler\Url
 */
use Doody\Crawler\Mongo\Mongo;

/**
 * Class UrlGuardian
 * @package Doody\Crawler\Url
 */
final class UrlGuardian
{
    /**
     * @var null|UrlGuardian
     */
    private static $instance = null;
    /**
     * @var array
     */
    private $cache = [];

    /**
     * UrlGuardian constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return UrlGuardian
     */
    public static function Instance() : UrlGuardian
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    public function shouldCrawl(Url $url): bool
    {
        return $url->isValid() && !$this->hasChilds($url);
    }

    /**
     * @param Relation $relation
     *
     * @return bool
     */
    public function shouldInsert(Relation $relation): bool
    {
        return $relation->isValid() && !$this->hasRelations($relation);
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    public function hasChilds(Url $url) : bool
    {
        if (array_key_exists($url->asString(), $this->cache)) {
            return $this->cache[$url->asString()];
        }

        if ($this->countChildsOf($url) !== 0) {
            $this->cache[$url->asString()] = true;

            return true;
        }

        return false;
    }

    /**
     * @param Relation $relation
     *
     * @return bool
     */
    public function hasRelations(Relation $relation) : bool
    {
        if (array_key_exists($relation->asString(), $this->cache)) {
            return $this->cache[$relation->asString()];
        }

        if ($this->countRelation($relation) !== 0) {
            $this->cache[$relation->asString()] = true;

            return true;
        }

        return false;
    }

    /**
     * @param Url $url
     *
     * @return int
     */
    public function countChildsOf(Url $url): int
    {
        return Mongo::Instance()->getCollection()->count(
            [
                'in' => [
                    '$elemMatch' => [
                        '$eq' => $url->getBaseUrl()
                    ]
                ]
            ]
        );
    }

    /**
     * @param Relation $relation
     *
     * @return int
     */
    public function countRelation(Relation $relation): int
    {
        return Mongo::Instance()->getCollection()->count(
            [
                'url' => $relation->getChild()->asString(),
                'in'  => [
                    '$elemMatch' => [
                        '$eq' => $relation->getParent()->getBaseUrl()
                    ]
                ]
            ]
        );
    }
}