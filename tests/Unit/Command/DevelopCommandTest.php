<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Command\DevelopCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Version\Version;

/**
 * Class DevelopCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
class DevelopCommandTest extends CommandTest
{

    /**
     * setup command class
     */
    public function getCommandClass()
    {
        return DevelopCommand::class;
    }

    /**
     * @test
     * @expectedException \Hoogi91\ReleaseFlow\Exception\VersionControlException
     */
    public function testThrowsExceptionIfCurrentlyNotInDevelopBranch()
    {
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('master');

        $this->command->run($this->input, $this->output);
    }

    /**
     * @test
     */
    public function testStartsDevelopVersion()
    {
        $this->setOptionValues([
            'dry-run' => true,
            'force'   => false,
        ]);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn([
            Version::fromString('2.3.4'),
        ]);

        // allow to create new development version
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm new hotfix version number
        $questionHelper->expects($this->exactly(1))->method('ask')->willReturn(true);

        // set local modifications to true after processing file providers
        $this->vcs->expects($this->once())->method('hasLocalModifications')->willReturn(true);

        // check if working copy is saved with correct message
        $this->vcs->expects($this->once())
            ->method('saveWorkingCopy')
            ->with($this->equalTo('Bump version to 2.3.5-dev'));

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }

    /**
     * @test
     */
    public function testStartsDevelopVersionWithoutConfirm()
    {
        $this->setOptionValues([
            'dry-run' => true,
            'force'   => false,
        ]);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn([
            Version::fromString('2.3.4'),
        ]);

        // allow to create new development version
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm new hotfix version number
        $questionHelper->expects($this->exactly(1))->method('ask')->willReturn(false);

        // check if finish hotfix commands are executed
        $this->vcs->expects($this->never())->method('hasLocalModifications');

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }

    /**
     * @test
     */
    public function testStartsDevelopVersionForce()
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

        // set local modifications to true after processing file providers
        $this->vcs->expects($this->once())->method('hasLocalModifications')->willReturn(true);

        // check if working copy is saved with correct message
        $this->vcs->expects($this->once())
            ->method('saveWorkingCopy')
            ->with($this->equalTo('Bump version to 2.3.5-dev'));

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }
}