<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Configuration;

use Hoogi91\ReleaseFlow\Configuration\Composer;
use Hoogi91\ReleaseFlow\Exception\ComposerException;
use Hoogi91\ReleaseFlow\VersionControl\GitFlowVersionControl;
use PHPUnit\Framework\TestCase;

/**
 * Class ComposerTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Configuration
 */
class ComposerTest extends TestCase
{
    /**
     * @var Composer
     */
    protected $composer;

    public function setUp(): void
    {
        parent::setUp();
        $this->composer = new Composer(PHP_WORKING_DIRECTORY . '/fixtures/Configuration');
    }

    public function testThrowsExceptionWhenComposerFileIsNotFound(): void
    {
        $this->expectException(ComposerException::class);
        new Composer(PHP_WORKING_DIRECTORY . '/not-existing-directory/');
    }

    public function testConstructorWithValidPath(): void
    {
        $this->assertEquals(
            PHP_WORKING_DIRECTORY . '/fixtures/Configuration/composer.json',
            $this->composer->getFileLocation()
        );
    }

    /**
     * @depends testConstructorWithValidPath
     */
    public function testFileLocationAndReadingConfiguration(): void
    {
        // check package name and version
        $this->assertEquals('hoogi91/composer-test-package', $this->composer->getConfig('name'));
        $this->assertEquals('0.2.0', $this->composer->getVersion());

        // check for non-existing keys if default value is returned
        $this->assertEquals('my-default-value', $this->composer->getConfig('not-existing', 'my-default-value'));

        // check if array can be read
        $this->assertInternalType('array', $require = $this->composer->getConfig('require'));
        $this->assertEquals('~2.0', $require['symfony/console']);
    }

    /**
     * @depends testConstructorWithValidPath
     */
    public function testReadingExtraConfiguration(): void
    {
        // check getting extra configuration
        $this->assertInternalType('array', $extraConfig = $this->composer->getExtra());
        $this->assertArrayHasKey('provider', $extraConfig);
        $this->assertArrayHasKey('vcs', $extraConfig);

        // check for non-existing keys if default value is returned
        $this->assertEquals('my-default-value', $this->composer->getExtra('not-existing', 'my-default-value'));

        // validate vcs class name
        $this->assertEquals(GitFlowVersionControl::class, $this->composer->getVersionControlClass());

        // validate version string
        $this->assertInternalType('array', $providerClasses = $this->composer->getProviderClasses());
        $this->assertEquals('Vendor\\MyReleaseFlowExtension\\FileProvder\\MyFileProvider', $providerClasses[0]);
    }
}
