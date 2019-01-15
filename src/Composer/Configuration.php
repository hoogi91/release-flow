<?php

namespace Hoogi91\ReleaseFlow\Composer;

use Hoogi91\ReleaseFlow\Exception\ComposerException;
use Hoogi91\ReleaseFlow\VersionControl\GitVersionControl;

/**
 * Class Configuration
 * @package Hoogi91\ReleaseFlow\Composer
 */
class Configuration
{
    /**
     * @var string
     */
    protected $fileLocation = '';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Configuration constructor.
     *
     * @param string $workingDirectory
     *
     * @throws ComposerException
     */
    public function __construct(string $workingDirectory)
    {
        $this->fileLocation = rtrim($workingDirectory, '/') . '/composer.json';
        if (!is_file($this->fileLocation)) {
            throw new ComposerException(sprintf('composer.json couldn\'t be located in %s', $workingDirectory));
        }
        $this->configuration = json_decode(file_get_contents($this->fileLocation), true) ?: [];
    }

    /**
     * @return string
     */
    public function getFileLocation(): string
    {
        return $this->fileLocation;
    }

    /**
     * return composer json configuration
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array|string|int
     */
    public function getConfig($key = null, $default = null)
    {
        return $key ? ($this->configuration[$key] ?? $default) : $this->configuration;
    }

    /**
     * return this package related extra configuration
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array|string|int
     */
    public function getExtra($key = null, $default = null)
    {
        $extraConfig = $this->getConfig('extra') ?? [];
        $packageExtraConfig = $extraConfig['hoogi91/release-flow'] ?? [];
        return $key ? ($packageExtraConfig[$key] ?? $default) : $packageExtraConfig;
    }

    /**
     * get version control class from composer with fallback to Git Version control
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getConfig('version');
    }

    /**
     * get version control class from composer with fallback to Git Version control
     *
     * @return string
     */
    public function getVersionControlClass(): string
    {
        return $this->getExtra('vcs', GitVersionControl::class);
    }

    /**
     * get version control class from composer with fallback to Git Version control
     *
     * @return array
     */
    public function getProviderClasses(): array
    {
        return $this->getExtra('provider', []);
    }
}