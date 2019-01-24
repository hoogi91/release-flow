<?php

namespace Hoogi91\ReleaseFlow\Utility;

/**
 * Class LogUtility
 * @package Hoogi91\ReleaseFlow\Utility
 * @codeCoverageIgnore
 */
class LogUtility
{

    const LOG_FILE = 'release-flow.log';

    const SEVERITY_INFO = 'INFO';
    const SEVERITY_WARNING = 'WARNING';
    const SEVERITY_ERROR = 'ERROR';

    /**
     * @param string $message
     * @param string $severity
     *
     * @return bool
     */
    public static function log($message, $severity = self::SEVERITY_INFO)
    {
        return error_log(vsprintf('[%s][%s] %s', [
            date('d/M/Y:H:i:s O'),
            $severity,
            $message,
        ]), 3, getcwd() . static::LOG_FILE);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function info($message)
    {
        return self::log($message, self::SEVERITY_INFO);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function warning($message)
    {
        return self::log($message, self::SEVERITY_WARNING);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function error($message)
    {
        return self::log($message, self::SEVERITY_ERROR);
    }
}