<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Configuration;

use Hoogi91\ReleaseFlow\Configuration\Composer;
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

    public function setUp()
    {
        parent::setUp();
        $this->composer = new Composer(PHP_WORKDIR . '/fixtures/Configuration');
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\ComposerException
     */
    public function testThrowsExceptionWhenComposerFileIsNotFound()
    {
        new Composer(PHP_WORKDIR . '/not-existing-directory/');
    }

    /**
     * @test
     */
    public function testConstructorWithValidPath()
    {
        $this->assertEquals(PHP_WORKDIR . '/fixtures/Configuration/composer.json', $this->composer->getFileLocation());
    }

    /**
     * @test
     * @depends testConstructorWithValidPath
     */
    public function testFileLocationAndReadingConfiguration()
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
     * @test
     * @depends testConstructorWithValidPath
     */
    public function testReadingExtraConfiguration()
    {
        // check getting extra configuration
        $this->assertInternalType('array', $extraConfig = $this->composer->getExtra());
        $this->assertArrayHasKey('provider', $extraConfig);
        $this->assertArrayHasKey('vcs', $extraConfig);

        // check for non-existing keys if default value is returned
        $this->assertEquals('my-default-value', $this->composer->getExtra('not-existing', 'my-default-value'));

        // validate vcs class name
        $this->assertEquals(
            'Hoogi91\\ReleaseFlow\\VersionControl\\GitFlowVersionControl',
            $this->composer->getVersionControlClass()
        );

        // validate version string
        $this->assertInternalType('array', $providerClasses = $this->composer->getProviderClasses());
        $this->assertEquals('Vendor\\MyReleaseFlowExtension\\FileProvder\\MyFileProvider', $providerClasses[0]);
    }
}
