<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\VersionControl;

use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Hoogi91\ReleaseFlow\VersionControl\GitFlowVersionControl;
use org\bovigo\vfs\vfsStream;

/**
 * Class GitFlowVersionControlTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\VersionControl
 */
class GitFlowVersionControlTest extends GitVersionControlTest
{
    /**
     * @var GitFlowVersionControl
     */
    protected $vcs;

    public function setUp(): void
    {
        parent::setUp();
        // get version control from current GIT repository
        $this->vcs = new GitFlowVersionControl(PHP_WORKING_DIRECTORY);

        // update git property in vcs
        $reflectionProperty = new \ReflectionProperty(GitFlowVersionControl::class, 'git');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->vcs, $this->git);
    }

    public function testThrowsExceptionOnWorkingDirectoryIsNotGitRepository(): void
    {
        $this->expectException(VersionControlException::class);
        new GitFlowVersionControl(vfsStream::create([], vfsStream::newDirectory('no-repo'))->url());
    }

    public function startCommandDataProvider(): array
    {
        return array(
            [GitFlowVersionControl::RELEASE, 1],
            [GitFlowVersionControl::HOTFIX, 1],
            [GitFlowVersionControl::DEVELOP, 0],
        );
    }

    public function finishCommandDataProvider(): array
    {
        return array(
            [GitFlowVersionControl::RELEASE, true, 1],
            [GitFlowVersionControl::HOTFIX, false, 1],
            [GitFlowVersionControl::DEVELOP, false, 0],
        );
    }
}
