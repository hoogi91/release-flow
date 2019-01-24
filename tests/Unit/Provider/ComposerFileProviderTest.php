<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Provider;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Configuration\Composer;
use Hoogi91\ReleaseFlow\FileProvider\ComposerFileProvider;
use PHPUnit\Framework\TestCase;
use Version\Version;

/**
 * Class ComposerFileProviderTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Provider
 */
class ComposerFileProviderTest extends TestCase
{
    /**
     * @var Application|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $application;

    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $composer;

    /**
     * @var string
     */
    protected $composerTestFile;

    /**
     * @var ComposerFileProvider
     */
    protected $fileProvider;

    /**
     * setup mocks and file provider class
     */
    public function setUp()
    {
        parent::setUp();
        $this->composer = $this->getMockBuilder(Composer::class)->disableOriginalConstructor()->getMock();
        $this->application = $this->getMockBuilder(Application::class)->getMock();
        $this->application->method('getComposer')->willReturn($this->composer);
        $this->fileProvider = new ComposerFileProvider();

        $this->composerTestFile = __DIR__ . '/composer-testfile.json';
        file_put_contents($this->composerTestFile, '');
    }

    public function tearDown()
    {
        parent::tearDown();
        unlink($this->composerTestFile);
    }

    /**
     * @test
     */
    public function testProcessBreakOnMissingComposerFile()
    {
        $this->composer->expects($this->once())->method('getFileLocation')->willReturn(__DIR__); // should be false
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
    }

    /**
     * @test
     */
    public function testProcessBreakOnMissingComposerVersion()
    {
        $this->composer->expects($this->once())->method('getFileLocation')->willReturn($this->composerTestFile);
        $this->composer->expects($this->once())->method('getVersion')->willReturn('');
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\FileProviderException
     */
    public function testThrowsExceptionWhenComposerVersionIsNotInSemVerFormat()
    {
        $this->composer->expects($this->once())->method('getFileLocation')->willReturn($this->composerTestFile);
        $this->composer->expects($this->once())->method('getVersion')->willReturn('2-3-3');
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\FileProviderException
     */
    public function testThrowsExceptionWhenComposerVersionIsGreaterThanNewVersion()
    {
        $this->composer->expects($this->once())->method('getFileLocation')->willReturn($this->composerTestFile);
        $this->composer->expects($this->once())->method('getVersion')->willReturn('2.3.4');
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
    }

    /**
     * @test
     */
    public function testSavingOfComposerContent()
    {
        $this->composer->expects($this->exactly(2))->method('getFileLocation')->willReturn($this->composerTestFile);
        $this->composer->expects($this->once())->method('getVersion')->willReturn('2.3.3');
        $this->composer->expects($this->once())->method('getConfig')->willReturn([
            "name"        => "hoogi91/composer-test-package",
            "version"     => "2.2.0",
            "description" => "Composer Test Package",
            "require"     => [
                "php"             => ">=5.3.3",
                "symfony/console" => "~2.0",
            ],
        ]);
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);

        // validate json content in temporary test file
        $this->assertJsonFileEqualsJsonFile(__DIR__ . '/output-composer.json', $this->composerTestFile);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\FileProviderException
     */
    public function testFailSavingOfComposerContent()
    {
        $this->markTestSkipped('Implement vfsStream to test file_put_contents in virtual file system!');

        // first return existing file to not break processing
        // ... then return non-writable path to break file_put_content
        $this->composer->expects($this->exactly(2))->method('getFileLocation')->willReturn($this->composerTestFile);

        // TODO: update test files permission to break file_put_contents

        // only try to write version into json file
        $this->composer->expects($this->once())->method('getVersion')->willReturn('2.3.3');
        $this->composer->expects($this->once())->method('getConfig')->willReturn([]);
        $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
    }

    /**
     * @test
     */
    public function testDryRunReturnsAlwaysTrue()
    {
        $this->composer->expects($this->once())->method('getFileLocation')->willReturn($this->composerTestFile);
        $this->composer->expects($this->once())->method('getVersion')->willReturn('2.3.3');

        // enabling dry run should simply return true and leave composer test file empty
        $this->fileProvider->enableDryRun();
        $result = $this->fileProvider->process(Version::fromString('2.3.4'), $this->application);
        $this->assertTrue($result);

        // validate that json content in temporary test file is empty
        $this->assertEmpty(file_get_contents($this->composerTestFile));
    }
}
