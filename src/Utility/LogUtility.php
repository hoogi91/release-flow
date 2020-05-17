<?php

namespace Hoogi91\ReleaseFlow\Utility;

/**
 * Class LogUtility
 * @package Hoogi91\ReleaseFlow\Utility
 * @codeCoverageIgnore
 */
class LogUtility
{

    private const LOG_FILE = 'release-flow.log';
    private const SEVERITY_INFO = 'INFO';
    private const SEVERITY_WARNING = 'WARNING';
    private const SEVERITY_ERROR = 'ERROR';

    /**
     * @param string $message
     * @param string $severity
     *
     * @return bool
     */
    public static function log($message, $severity = self::SEVERITY_INFO): bool
    {
        $logFile = PathUtility::getCwd() . '/' . static::LOG_FILE;
        return error_log(
            vsprintf(
                '[%s][%s] %s',
                [
                    date('d/M/Y:H:i:s O'),
                    $severity,
                    $message,
                ]
            ),
            3,
            $logFile
        );
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function info($message): bool
    {
        return self::log($message, self::SEVERITY_INFO);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function warning($message): bool
    {
        return self::log($message, self::SEVERITY_WARNING);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public static function error($message): bool
    {
        return self::log($message, self::SEVERITY_ERROR);
    }
}
