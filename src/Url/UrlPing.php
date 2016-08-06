<?php

namespace Doody\Crawler\Url;

use Dgame\HttpClient\HttpClient;

/**
 * Class Ping
 * @package Doody\Crawler\Url
 */
final class UrlPing
{
    /**
     * @var null|UrlPing
     */
    private static $instance = null;
    /**
     * @var HttpClient|null
     */
    private $client = null;

    /**
     * Ping constructor.
     */
    private function __construct()
    {
        $this->client = new HttpClient();
        $this->client->disable(CURLOPT_VERBOSE)->enable(CURLOPT_NOBODY);
    }

    /**
     * @return UrlPing
     */
    public static function Instance() : UrlPing
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isAttainable(string $url) : bool
    {
        return $this->client->get($url)->send()->getStatus()->isSuccess();
    }
}