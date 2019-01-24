<?php

namespace Hoogi91\ReleaseFlow\Tests\VersionControl;

use Hoogi91\ReleaseFlow\VersionControl\GitVersionControl;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use TQ\Git\Cli\Binary;
use TQ\Git\Repository\Repository;
use TQ\Vcs\Cli\CallException;
use Version\Version;

/**
 * Class GitVersionControlTest
 * @package Hoogi91\ReleaseFlow\Tests\VersionControl
 */
class GitVersionControlTest extends TestCase
{
    /**
     * @var GitVersionControl
     */
    protected $vcs;

    /**
     * @var Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $git;

    /**
     * @var Binary|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $gitBinary;

    public function setUp()
    {
        parent::setUp();

        $this->gitBinary = $this->getMockBuilder(Binary::class)->disableOriginalConstructor()->setMethods([
            'tag',
        ])->getMock();
        $this->git = $this->createMock(Repository::class);
        $this->git->method('getGit')->willReturn($this->gitBinary);

        // get version control from current GIT repository
        $this->vcs = new GitVersionControl(dirname(__DIR__, 3));

        // update git property in vcs
        $reflectionProperty = new \ReflectionProperty(GitVersionControl::class, 'git');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->vcs, $this->git);

    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionOnWorkingDirectoryIsNotGitRepository()
    {
        new GitVersionControl('/tmp');
    }

    /**
     * @test
     */
    public function testGettingBranches()
    {
        $this->git->expects($this->once())->method('getBranches')->willReturn([
            'develop',
            'master',
        ]);
        $this->assertCount(2, $this->vcs->getBranches());
    }

    /**
     * @test
     */
    public function testGettingCurrentBranch()
    {
        $this->git->expects($this->once())->method('getCurrentBranch')->willReturn('develop');
        $this->assertEquals('develop', $this->vcs->getCurrentBranch());
    }

    /**
     * @test
     */
    public function testHasLocalModifications()
    {
        $this->git->expects($this->once())->method('isDirty')->willReturn(true);
        $this->assertTrue($this->vcs->hasLocalModifications());
    }

    /**
     * @test
     */
    public function testGettingTags()
    {
        $tagClass = new class
        {
            public function getStdOut()
            {
                return implode(PHP_EOL, [
                    '1.0.0',
                    '1.0.1',
                    '1.0.2',
                ]);
            }
        };

        $this->gitBinary->expects($this->once())->method('tag')->willReturn($tagClass);
        $tags = $this->vcs->getTags();

        $this->assertCount(3, $tags);
        $this->assertInstanceOf(Version::class, $tags[0]);
        $this->assertEquals('1.0.2', $tags[2]->getVersionString());
    }

    /**
     * @test
     */
    public function testGettingTagsWithInvalidSemVer()
    {
        $this->markTestSkipped('Implement vfsStream to test invalid SemVer strings => currently triggers LogUtility!');

        $tagClass = new class
        {
            public function getStdOut()
            {
                return implode(PHP_EOL, [
                    '1.0.0',
                    '1.0.1',
                    '1-0-2',
                    '1.0.3',
                ]);
            }
        };

        $this->gitBinary->expects($this->once())->method('tag')->willReturn($tagClass);
        $tags = $this->vcs->getTags();

        $this->assertCount(3, $tags);
        $this->assertInstanceOf(Version::class, $tags[0]);
        $this->assertEquals('1.0.3', $tags[2]->getVersionString());
    }

    /**
     * @test
     */
    public function testRevertingWorkingCopy()
    {
        $this->git->expects($this->once())->method('reset');
        $this->assertTrue($this->vcs->revertWorkingCopy());
    }

    /**
     * @test
     */
    public function testRevertingWorkingCopyFailed()
    {
        $this->markTestSkipped('Implement vfsStream to test exception on revert => triggers LogUtility!');

        $this->git->expects($this->once())->method('reset')->willThrowException($this->createMock(CallException::class));
        $this->assertFalse($this->vcs->revertWorkingCopy());
    }

    /**
     * @test
     */
    public function testRevertingWorkingCopyOnDryRun()
    {
        $this->vcs->setDryRun($this->createMock(ConsoleOutput::class));
        $this->git->expects($this->never())->method('reset');
        $this->assertTrue($this->vcs->revertWorkingCopy());
    }

    /**
     * @test
     */
    public function testSavingWorkingCopy()
    {
        $this->git->expects($this->once())->method('add');
        $this->git->expects($this->once())->method('commit')->with($this->equalTo('Commit Message'));
        $this->assertTrue($this->vcs->saveWorkingCopy('Commit Message'));
    }

    /**
     * @test
     */
    public function testSavingWorkingCopyFailed()
    {
        $this->markTestSkipped('Implement vfsStream to test exception on revert => triggers LogUtility!');

        $this->git->expects($this->once())->method('add');
        $this->git->expects($this->once())->method('commit')->willThrowException($this->createMock(CallException::class));
        $this->assertFalse($this->vcs->saveWorkingCopy('Commit Message'));
    }

    /**
     * @test
     */
    public function testSavingWorkingCopyOnDryRun()
    {
        $this->vcs->setDryRun($this->createMock(ConsoleOutput::class));
        $this->git->expects($this->never())->method('add');
        $this->git->expects($this->never())->method('commit');
        $this->assertTrue($this->vcs->saveWorkingCopy('Commit Message'));
    }

    /**
     * @test
     */
    public function testGettingStartCommands()
    {
        $version = Version::fromString('1.0.0');

        // make command getter methods accessible
        $startCommandsMethod = new \ReflectionMethod($this->vcs, 'getStartCommands');
        $startCommandsMethod->setAccessible(true);

        // single start command
        $releaseCommands = $startCommandsMethod->invoke($this->vcs, $version, GitVersionControl::RELEASE);
        $this->assertCount(1, $releaseCommands);

        // single start command
        $hotfixCommands = $startCommandsMethod->invoke($this->vcs, $version, GitVersionControl::HOTFIX);
        $this->assertCount(1, $hotfixCommands);

        // zero cause finish command has no actions from other branches than releases and hotfixes
        $notSupportedBranchCommands = $startCommandsMethod->invoke($this->vcs, $version, GitVersionControl::DEVELOP);
        $this->assertCount(0, $notSupportedBranchCommands);
    }

    /**
     * @test
     */
    public function testGettingFinishCommands()
    {
        $version = Version::fromString('1.0.0');

        $finishCommandsMethod = new \ReflectionMethod($this->vcs, 'getFinishCommands');
        $finishCommandsMethod->setAccessible(true);

        // 7 commands cause publish command is activated
        $releaseCommands = $finishCommandsMethod->invoke($this->vcs, $version, GitVersionControl::RELEASE, true);
        $this->assertCount(7, $releaseCommands);

        // 6 commands cause publish command is deactivated
        $hotfixCommands = $finishCommandsMethod->invoke($this->vcs, $version, GitVersionControl::HOTFIX);
        $this->assertCount(6, $hotfixCommands);

        // zero cause finish command has no actions from other branches than releases and hotfixes
        $notSupportedBranchCommands = $finishCommandsMethod->invoke($this->vcs, $version, GitVersionControl::DEVELOP);
        $this->assertCount(0, $notSupportedBranchCommands);
    }

}