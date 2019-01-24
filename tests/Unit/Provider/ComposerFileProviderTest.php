<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Provider;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Configuration\Composer;
use Hoogi91\ReleaseFlow\FileProvider\ComposerFileProvider;
use org\bovigo\vfs\vfsStream;
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
     * @var string
     */
    protected $composerOutputFile;

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

        // create virtual filesystem root and composer test file
        $root = vfsStream::setup('root');
        $this->composerTestFile = vfsStream::newFile('composer-testfile.json')->at($root)->setContent('')->url();
        $this->composerOutputFile = vfsStream::newFile('composer-output.json')->at($root)->setContent(file_get_contents(__DIR__ . '/output-composer.json'))->url();
    }

    /**
     * @test
     */
    public function testProcessBreakOnMissingComposerFile()
    {
        $this->composer->expects($this->once())
            ->method('getFileLocation')
            ->willReturn(vfsStream::setup('not-existing')->url()); // should be false

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
        $this->assertJsonFileEqualsJsonFile($this->composerOutputFile, $this->composerTestFile);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\FileProviderException
     */
    public function testFailSavingOfComposerContent()
    {
        // first return existing file to not break processing
        // ... then failed for non-writable path to break file_put_content
        $this->composer->expects($this->exactly(2))->method('getFileLocation')->willReturn($this->composerTestFile);
        vfsStream::setQuota(0); // disallow writing to break writing file

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
