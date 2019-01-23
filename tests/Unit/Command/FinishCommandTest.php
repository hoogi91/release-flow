<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * Class FinishCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
class FinishCommandTest extends CommandTest
{

    /**
     * setup command class
     */
    public function getCommandClass()
    {
        return FinishCommand::class;
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionIfNoFlowExists()
    {
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'someOtherBranch']);

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionIfNotInTheFlow()
    {
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'release/1.0.3']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('develop');

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     */
    public function testFinishesRelease()
    {
        $this->setOptionValues(['dry-run' => true]);

        // simulate that we are in release branch
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'release/1.0.3']);
        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('release/1.0.3');

        // simulate that no local modifications are available (before and after executing file provider)
        $this->vcs->expects($this->any())->method('hasLocalModifications')->willReturn(false);

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm finishing release and publishing
        $questionHelper->expects($this->exactly(2))->method('ask')->willReturn(true);

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateFinishReleaseCommands']);

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     */
    public function testFinishReleaseWithAsksForLocalModifications()
    {
        $this->setOptionValues(['dry-run' => true]);

        // simulate that we are in release branch
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'release/1.0.3']);
        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('release/1.0.3');

        // simulate that local modifications are available (before and after executing file provider)
        $this->vcs->expects($this->exactly(2))->method('hasLocalModifications')->willReturn(true);

        // expect exactly 2 calls of saveWorkingCopy after local modifications have been found
        $this->vcs->expects($this->exactly(2))->method('saveWorkingCopy');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // 1. confirm saving local modifications
        // 2. setting commit message
        // 3. confirm commit message
        // 4. confirm finishing release and
        // 5. confirm publishing
        $questionHelper->expects($this->exactly(5))->method('ask')->willReturn(true);

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateFinishReleaseCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function validateFinishReleaseCommands($commands)
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(7, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git checkout master', $commands[0]);
        $this->assertEquals('git merge --no-ff release/1.0.3', $commands[1]);
        $this->assertEquals('git tag -a 1.0.3 -m "Tagging version 1.0.3"', $commands[2]);
        $this->assertEquals('git push origin --tags', $commands[3]);
        $this->assertEquals('git checkout develop', $commands[4]);
        $this->assertEquals('git merge --no-ff release/1.0.3', $commands[5]);
        $this->assertEquals('git branch -d release/1.0.3', $commands[6]);
    }

    /**
     * @test
     */
    public function testFinishesHotfix()
    {
        $this->setOptionValues(['dry-run' => true]);

        // simulate that we are in release branch
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'hotfix/1.0.3']);
        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('hotfix/1.0.3');

        // simulate that no local modifications are available (before and after executing file provider)
        $this->vcs->expects($this->any())->method('hasLocalModifications')->willReturn(false);

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm finishing release and publishing
        $questionHelper->expects($this->exactly(2))->method('ask')->willReturn(true);

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateFinishHotfixCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function validateFinishHotfixCommands($commands)
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(7, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git checkout master', $commands[0]);
        $this->assertEquals('git merge --no-ff hotfix/1.0.3', $commands[1]);
        $this->assertEquals('git tag -a 1.0.3 -m "Tagging version 1.0.3"', $commands[2]);
        $this->assertEquals('git push origin --tags', $commands[3]);
        $this->assertEquals('git checkout develop', $commands[4]);
        $this->assertEquals('git merge --no-ff hotfix/1.0.3', $commands[5]);
        $this->assertEquals('git branch -d hotfix/1.0.3', $commands[6]);
    }
}