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
     * Language constructor.
     */
    private function __construct()
    {
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
        $langDetect = new TextLanguageDetect();
        $langDetect->setNameMode(2);

        $r = $langDetect->detect($content, 1);

        return empty($r) ? 'en' : array_keys($r)[0];
    }
}