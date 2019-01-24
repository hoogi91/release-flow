<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Version\Version;

/**
 * Class HotfixCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
class HotfixCommandTest extends CommandTest
{

    /**
     * setup command class
     */
    public function getCommandClass()
    {
        return HotfixCommand::class;
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionIfActiveFlowExists()
    {
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'hotfix/1.0.3']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('develop');

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionIfAlreadyInTheFlow()
    {
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'hotfix/1.0.3']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('hotfix/1.0.3');

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     */
    public function testStartsHotfix()
    {
        $this->setOptionValues([
            'dry-run' => true,
            'force'   => false,
        ]);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn([
            Version::fromString('2.3.4'),
        ]);

        // allow to create new hotfix branch
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm new hotfix version number
        $questionHelper->expects($this->exactly(1))->method('ask')->willReturn(true);

        // check if finish hotfix commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateStartHotfixCommands']);

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     */
    public function testStartsHotfixForce()
    {
        $this->setOptionValues([
            'dry-run' => true,
            'force'   => true,
        ]);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn([
            Version::fromString('2.3.4'),
        ]);

        // allow to create new hotfix branch
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // check that ask method isn't executed
        $questionHelper->expects($this->never())->method('ask');

        // check if finish hotfix commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateStartHotfixCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function validateStartHotfixCommands($commands)
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git checkout -b hotfix/2.3.5 master', $commands[0]);
    }
}