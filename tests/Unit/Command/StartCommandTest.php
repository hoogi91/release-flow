<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Version\Version;

/**
 * Class StartCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
class StartCommandTest extends CommandTest
{

    /**
     * setup command class
     */
    public function getCommandClass(): string
    {
        return StartCommand::class;
    }

    public function testThrowsExceptionIfActiveFlowExists(): void
    {
        $this->expectException(VersionControlException::class);
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'release/1.0.3']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('develop');

        $this->command->run($this->input, $this->output);
    }

    public function testThrowsExceptionIfAlreadyInTheFlow(): void
    {
        $this->expectException(VersionControlException::class);
        $this->vcs->expects($this->once())
            ->method('getBranches')
            ->willReturn(['master', 'develop', 'release/1.0.3']);

        $this->vcs->expects($this->atLeastOnce())
            ->method('getCurrentBranch')
            ->willReturn('release/1.0.3');

        $this->command->run($this->input, $this->output);
    }

    public function testStartsMinorRelease(): void
    {
        $this->setOptionValues(
            [
                'dry-run' => true,
                'force' => false,
            ]
        );

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn(
            [
                Version::fromString('2.3.4'),
            ]
        );

        // allow to create new release branch
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm first ask to create minor release and second confirm new version
        $questionHelper->expects($this->at(0))->method('ask')->willReturn('minor');
        $questionHelper->expects($this->at(1))->method('ask')->willReturn(true);

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateStartMinorReleaseCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function testStartsMajorReleaseWithArgument(): void
    {
        $this->setOptionValues(
            [
                'dry-run' => true,
                'increment' => 'major',
                'force' => false,
            ]
        );

        // make sure that increment option will be read from options
        $this->input->expects($this->once())->method('hasOption')->with($this->equalTo('increment'))->willReturn(true);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn(
            [
                Version::fromString('2.3.4'),
            ]
        );

        // allow to create new release branch
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // confirm new release version number
        $questionHelper->expects($this->exactly(1))->method('ask')->willReturn(true);

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateStartMajorReleaseCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function testStartsMajorReleaseForce(): void
    {
        $this->setOptionValues(
            [
                'dry-run' => true,
                'increment' => 'major',
                'force' => true,
            ]
        );

        // make sure that increment option will be read from options
        $this->input->expects($this->once())->method('hasOption')->with($this->equalTo('increment'))->willReturn(true);

        // set current tagged version to 2.3.4
        $this->vcs->method('getTags')->willReturn(
            [
                Version::fromString('2.3.4'),
            ]
        );

        // allow to create new release branch
        $this->vcs->expects($this->once())->method('getBranches')->willReturn(['master', 'develop']);
        $this->vcs->expects($this->atLeastOnce())->method('getCurrentBranch')->willReturn('develop');

        /** @var QuestionHelper|\PHPUnit_Framework_MockObject_MockObject $questionHelper */
        $questionHelper = $this->helperSet->get('question');

        // check that ask method isn't executed
        $questionHelper->expects($this->never())->method('ask');

        // check if finish release commands are executed
        $this->vcs->expects($this->once())
            ->method('executeCommands')
            ->willReturnCallback([$this, 'validateStartMajorReleaseCommands']);

        $this->command->run($this->input, $this->output);
    }

    public function validateStartMinorReleaseCommands($commands): string
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git checkout -b release/2.4.0 develop', $commands[0]);

        return '';
    }

    public function validateStartMajorReleaseCommands($commands): string
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git checkout -b release/3.0.0 develop', $commands[0]);

        return '';
    }
}
