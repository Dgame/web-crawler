<?php

namespace Doody\Crawler\Url;

use function Dgame\Iterator\Optional\none;
use function Dgame\Iterator\Optional\some;

/**
 * Class Url
 * @package Doody\Crawler
 */
final class Url
{
    /**
     * @var null|string
     */
    private $url = null;
    /**
     * @var null|bool
     */
    private $valid = null;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $url = trim($url);
        if (substr($url, 0, 4) !== 'http') {
            $url = 'http://' . ltrim($url, '/');
        }

        $this->url   = $url;
        $this->valid = none();
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid() : bool
    {
        if ($this->valid->isNone()) {
            $this->valid = some(filter_var($this->url, FILTER_VALIDATE_URL) !== false && strpos($this->url, 'mailto') === false);
        }

        return $this->valid->unwrap();
    }

    /**
     * @return string
     */
    public function asString() : string
    {
        return $this->url;
    }

    /**
     * Return the host part of an url
     * @return string
     */
    public function getBaseUrl()
    {
        $base = parse_url($this->url, PHP_URL_HOST);
        if (substr($base, 0, 4) !== 'www.') {
            $base = 'www.' . $base;
        }

        return $base;
    }
}
