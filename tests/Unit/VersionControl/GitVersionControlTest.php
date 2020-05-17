<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\VersionControl;

use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Hoogi91\ReleaseFlow\VersionControl\GitVersionControl;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Output\ConsoleOutput;
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Vcs\Cli\CallException;
use Version\Version;

/**
 * Class GitVersionControlTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\VersionControl
 */
class GitVersionControlTest extends TestCase
{
    /**
     * @var GitVersionControl
     */
    protected $vcs;

    /**
     * @var Repository|PHPUnit_Framework_MockObject_MockObject
     */
    protected $git;

    /**
     * @var Binary|PHPUnit_Framework_MockObject_MockObject
     */
    protected $gitBinary;

    /**
     * @var vfsStreamDirectory
     */
    protected $gitRepositoryPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->gitBinary = $this->getMockBuilder(Binary::class)->disableOriginalConstructor()->setMethods(
            [
                'tag',
            ]
        )->getMock();
        $this->git = $this->createMock(Repository::class);
        $this->git->method('getGit')->willReturn($this->gitBinary);

        // get version control from current GIT repository
        $this->vcs = new GitVersionControl(PHP_WORKING_DIRECTORY);

        // update git property in vcs
        $reflectionProperty = new \ReflectionProperty(GitVersionControl::class, 'git');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->vcs, $this->git);
    }

    public function testThrowsExceptionOnWorkingDirectoryIsNotGitRepository(): void
    {
        $this->expectException(VersionControlException::class);
        new GitVersionControl(vfsStream::create([], vfsStream::newDirectory('no-repo'))->url());
    }

    public function testGettingBranches(): void
    {
        $this->git->expects($this->once())->method('getBranches')->willReturn(
            [
                'develop',
                'master',
            ]
        );
        $this->assertCount(2, $this->vcs->getBranches());
    }

    public function testGettingCurrentBranch(): void
    {
        $this->git->expects($this->once())->method('getCurrentBranch')->willReturn('develop');
        $this->assertEquals('develop', $this->vcs->getCurrentBranch());
    }

    public function testHasLocalModifications(): void
    {
        $this->git->expects($this->once())->method('isDirty')->willReturn(true);
        $this->assertTrue($this->vcs->hasLocalModifications());
    }

    public function testGettingTags(): void
    {
        $tagClass = new class {
            public function getStdOut()
            {
                return implode(
                    PHP_EOL,
                    [
                        '1.0.0',
                        '1.0.1',
                        '1.0.2',
                    ]
                );
            }
        };

        $this->gitBinary->expects($this->once())->method('tag')->willReturn($tagClass);
        $tags = $this->vcs->getTags();

        $this->assertCount(3, $tags);
        $this->assertInstanceOf(Version::class, $tags[0]);
        $this->assertEquals('1.0.2', $tags[2]->getVersionString());
    }

    public function testGettingTagsWithInvalidSemVer(): void
    {
        $tagClass = new class {
            public function getStdOut()
            {
                return implode(
                    PHP_EOL,
                    [
                        '1.0.0',
                        '1.0.1',
                        '1-0-2',
                        '1.0.3',
                    ]
                );
            }
        };

        $this->gitBinary->expects($this->once())->method('tag')->willReturn($tagClass);
        $tags = $this->vcs->getTags();

        $this->assertCount(3, $tags);
        $this->assertInstanceOf(Version::class, $tags[0]);
        $this->assertEquals('1.0.3', $tags[2]->getVersionString());
    }

    public function testRevertingWorkingCopy(): void
    {
        $this->git->expects($this->once())->method('reset');
        $this->assertTrue($this->vcs->revertWorkingCopy());
    }

    public function testRevertingWorkingCopyFailed(): void
    {
        $this->git->expects($this->once())->method('reset')->willThrowException(
            $this->createMock(CallException::class)
        );
        $this->assertFalse($this->vcs->revertWorkingCopy());
    }

    public function testRevertingWorkingCopyOnDryRun(): void
    {
        $this->vcs->setDryRun($this->createMock(ConsoleOutput::class));
        $this->git->expects($this->never())->method('reset');
        $this->assertTrue($this->vcs->revertWorkingCopy());
    }

    public function testSavingWorkingCopy(): void
    {
        $this->git->expects($this->once())->method('add');
        $this->git->expects($this->once())->method('commit')->with($this->equalTo('Commit Message'));
        $this->assertTrue($this->vcs->saveWorkingCopy('Commit Message'));
    }

    public function testSavingWorkingCopyFailed(): void
    {
        $this->git->expects($this->once())->method('add');
        $this->git->expects($this->once())->method('commit')->willThrowException(
            $this->createMock(CallException::class)
        );
        $this->assertFalse($this->vcs->saveWorkingCopy('Commit Message'));
    }

    public function testSavingWorkingCopyOnDryRun(): void
    {
        $this->vcs->setDryRun($this->createMock(ConsoleOutput::class));
        $this->git->expects($this->never())->method('add');
        $this->git->expects($this->never())->method('commit');
        $this->assertTrue($this->vcs->saveWorkingCopy('Commit Message'));
    }

    /**
     * @dataProvider startCommandDataProvider
     */
    public function testGettingStartCommands($branchType, $assertCount): void
    {
        $version = Version::fromString('1.0.0');

        // make command getter methods accessible
        $startCommandsMethod = new \ReflectionMethod($this->vcs, 'getStartCommands');
        $startCommandsMethod->setAccessible(true);

        $commands = $startCommandsMethod->invoke($this->vcs, $version, $branchType);
        $this->assertCount($assertCount, $commands);
    }

    public function startCommandDataProvider(): array
    {
        return array(
            [GitVersionControl::RELEASE, 1],
            [GitVersionControl::HOTFIX, 1],
            [GitVersionControl::DEVELOP, 0],
        );
    }

    /**
     * @dataProvider finishCommandDataProvider
     */
    public function testGettingFinishCommands($branchType, $publish, $assertCount): void
    {
        $version = Version::fromString('1.0.0');

        $finishCommandsMethod = new \ReflectionMethod($this->vcs, 'getFinishCommands');
        $finishCommandsMethod->setAccessible(true);

        $commands = $finishCommandsMethod->invoke($this->vcs, $version, $branchType, $publish);
        $this->assertCount($assertCount, $commands);
    }

    public function finishCommandDataProvider(): array
    {
        return array(
            [GitVersionControl::RELEASE, true, 7],
            [GitVersionControl::HOTFIX, false, 6],
            [GitVersionControl::DEVELOP, false, 0],
        );
    }
}
