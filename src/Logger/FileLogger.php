<?php

namespace Doody\Crawler\Logger;

/**
 * Class FileLogger
 * @package Doody\Crawler\Logger
 */
final class FileLogger
{
    /**
     * @var null|FileLogger
     */
    private static $instance;
    /**
     * @var string
     */
    public $logFile = 'log.txt';
    /**
     * @var bool
     */
    private $doLog = true;

    /**
     * FileLogger constructor.
     */
    private function __construct()
    {
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    /**
     * @return FileLogger
     */
    public static function Instance() : FileLogger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     *
     */
    public function enable()
    {
        $this->doLog = true;
    }

    /**
     *
     */
    public function disable()
    {
        $this->doLog = false;
    }

    /**
     * @param string $message
     * @param array  ...$args
     *
     * @return FileLogger
     */
    public function log(string $message, ...$args) : FileLogger
    {
        if ($this->doLog) {
            if (!empty($args)) {
                $message = sprintf($message, ...$args);
            }

            $message = sprintf('[%s]: %s', date('d.m.Y H:i:s'), $message);
            @file_put_contents($this->logFile, $message . PHP_EOL, FILE_APPEND);
        }

        return $this;
    }
}