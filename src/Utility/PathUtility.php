<?php

namespace Hoogi91\ReleaseFlow\Utility;

/**
 * Class PathUtility
 * @package Hoogi91\ReleaseFlow\Utility
 * @codeCoverageIgnore
 */
class PathUtility
{

    /**
     * @return string
     */
    public static function getCwd(): string
    {
        $path = defined('PHP_WORKING_DIRECTORY') ? constant('PHP_WORKING_DIRECTORY') : getcwd();
        return rtrim((string)$path, '/\\');
    }
}
