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
     * @var UrlCache|null
     */
    private $cache = null;

    /**
     * UrlGuardian constructor.
     */
    private function __construct()
    {
        $this->cache = new UrlCache();
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
        if ($this->cache->shouldCrawl($url)) {
            $result = $url->isValid() && $this->countChildsOf($url) === 0;
            $this->cache->cacheShouldCrawl($url, $result);

            return $result;
        }

        return false;
    }

    /**
     * @param Relation $relation
     *
     * @return bool
     */
    public function shouldInsert(Relation $relation): bool
    {
        if ($this->cache->shouldInsert($relation)) {
            $result = $relation->isValid() && $this->countRelation($relation) === 0;
            $this->cache->cacheShouldInsert($relation, $result);

            return $result;
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