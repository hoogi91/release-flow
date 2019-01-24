<?php

namespace Hoogi91\ReleaseFlow\FileProvider;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Configuration\Composer;
use Hoogi91\ReleaseFlow\Exception\FileProviderException;
use Symfony\Component\Console\Application as SymfonyApplication;
use Version\Exception\InvalidVersionStringException;
use Version\Version;

/**
 * file provider to manipulate the composer file version
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ComposerFileProvider implements FileProviderInterface
{
    /**
     * @var bool
     */
    protected $dryRun = false;

    /**
     * sets indicator if current process shouldn't update file and keep current working copy
     *
     * @return void
     */
    public function enableDryRun()
    {
        $this->dryRun = true;
    }

    /**
     * @param Version                        $version
     * @param Application|SymfonyApplication $application
     *
     * @return bool true on successful processing and false if nothing happened
     * @throws FileProviderException if issue occurred and file provider version bump needs to stop and rollback
     */
    public function process(Version $version, SymfonyApplication $application): bool
    {
        // do not set/update composer.json if file doesn't exists
        if (is_file($application->getComposer()->getFileLocation()) === false) {
            return false;
        }

        // do not add version if current composer.json hasn't version set
        $composerVersion = $application->getComposer()->getVersion();
        if (empty($composerVersion)) {
            return false;
        }

        try {
            $composerVersion = Version::fromString($composerVersion);
            if ($composerVersion->isGreaterOrEqualTo($version)) {
                throw new FileProviderException('Current composer.json version shouldn\'t be greater or equal than new version.');
            }

            // add new version to composer file
            return $this->setVersionInFile($version, $application->getComposer());
        } catch (InvalidVersionStringException $e) {
            throw new FileProviderException('Current composer.json version can\'t be read. Please fix this issue first!');
        }
    }

    /**
     * Sets the new version in composer.json
     *
     * @param Version  $version
     * @param Composer $composer
     *
     * @return bool
     * @throws FileProviderException
     */
    protected function setVersionInFile(Version $version, Composer $composer)
    {
        if ($this->dryRun !== false) {
            return true;
        }

        // update composer version in config
        $json = $composer->getConfig();
        $json['version'] = $version->getVersionString();

        // try to save new composer.json pretty printed
        $bytesWritten = @file_put_contents(
            $composer->getFileLocation(),
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        if ($bytesWritten === false) {
            throw new FileProviderException('Writing composer.json with new version failed.');
        }
        return true;
    }
}