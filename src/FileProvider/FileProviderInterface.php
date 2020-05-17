<?php

namespace Hoogi91\ReleaseFlow\FileProvider;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Exception\FileProviderException;
use Version\Version;

/**
 * Interface FileProviderInterface
 * @package Hoogi91\ReleaseFlow\FileProvider
 */
interface FileProviderInterface
{

    /**
     * sets indicator if current process shouldn't update file and keep current working copy
     *
     * @return void
     */
    public function enableDryRun(): void;

    /**
     * @param Version $version
     * @param Application $application
     *
     * @return bool true on successful processing and false if nothing happened
     * @throws FileProviderException if issue occurred and file provider version bump needs to stop and rollback
     */
    public function process(Version $version, Application $application): bool;
}
