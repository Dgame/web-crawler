<?php

namespace Doody\Crawler\Url;

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
     * @var null|string
     */
    private $base = null;
    /**
     * @var bool
     */
    private $valid = false;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        if (strlen($url) >= 4 && strpos($url, 'mailto') === false && preg_match('#[a-z]+#iS', $url)) {
            $url = trim($url);
            if (substr($url, 0, 4) !== 'http') {
                $url = 'http://' . ltrim($url, '/');
            }

            $this->valid = true;//UrlPing::Instance()->isAttainable($url);
            $this->url   = $url;

            if ($this->valid) {
                $base = parse_url($url, PHP_URL_HOST);
                if (substr($base, 0, 4) !== 'www.') {
                    $base = 'www.' . $base;
                }

                $this->base = $base;
            }
        }
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->valid;
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
    public function getBaseUrl() : string
    {
        return $this->base;
    }
}
