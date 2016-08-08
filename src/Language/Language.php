<?php

namespace Doody\Crawler\Language;

use TextLanguageDetect\TextLanguageDetect;

/**
 * Class Language
 * @package Doody\Crawler\Language
 */
final class Language
{
    /**
     * @var null|Language
     */
    private static $instance = null;
    /**
     * @var null|TextLanguageDetect
     */
    private $detector = null;

    /**
     * Language constructor.
     */
    private function __construct()
    {
        $this->detector = new TextLanguageDetect();
        $this->detector->setNameMode(2);
    }

    /**
     * @return Language
     */
    public static function Instance() : Language
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public function detectLanguage(string $content)
    {
        $lang = $this->detector->detect($content, 1);

        return empty($lang) ? 'en' : array_keys($lang)[0];
    }
}