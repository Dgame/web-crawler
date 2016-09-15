<?php

namespace Doody\Crawler\Crawler;

use Doody\Crawler\Mongo\Mongo;
use Doody\Crawler\Url\Relation;

/**
 * Class DataRecorder
 * @package Doody\Crawler\Crawler
 */
final class DataRecorder
{
    const FILE = 'records.txt';

    /**
     * @var DataRecorder
     */
    private static $instance;

    /**
     * @var array
     */
    private $records = [];

    /**
     * DataRecorder constructor.
     */
    private function __construct()
    {
        $this->records = array_map(function(string $line) {
            return serialize($line);
        }, file(self::FILE));

        unlink(self::FILE);
    }

    /**
     *
     */
    public function __destruct()
    {
        file_put_contents(self::FILE, implode(PHP_EOL, array_map(function(array $record) {
            return serialize($record);
        }, $this->records)), FILE_APPEND);
    }

    /**
     *
     */
    private function __clone()
    {
    }

    /**
     * @return DataRecorder
     */
    public static function Instance(): DataRecorder
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param Relation $relation
     * @param Filter   $filter
     */
    public function append(Relation $relation, Filter $filter)
    {
        $this->records[$relation->asString()] = [
            'url' => $relation->getChild()->asString(),
            [
                '$set'      => [
                    'url'      => $relation->getChild()->asString(),
                    'base'     => $relation->getChild()->getBaseUrl(),
                    'content'  => $filter->getContent(),
                    'title'    => $filter->hasTitle() ? $filter->getTitle() : $relation->getChild()->getBaseUrl(),
                    'language' => Language::Instance()->detectLanguage($filter->getContent()),
                    'pr'       => 0,
                ],
                '$addToSet' => [
                    'in' => $relation->getParent()->asString(),
                ],
            ],
            [
                'upsert' => true,
            ]
        ];
    }

    /**
     *
     */
    public function apply()
    {
        if (DB_INSERT && DB_BULK_LIMIT <= count($this->records)) {
            Mongo::Instance()->getCollection()->insertMany($this->records);
            $this->records = [];
        }
    }
}