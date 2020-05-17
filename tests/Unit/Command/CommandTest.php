<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Command\AbstractFlowCommand;
use Hoogi91\ReleaseFlow\VersionControl\GitVersionControl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
abstract class CommandTest extends TestCase
{
    /**
     * @var InputInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $input;

    /**
     * @var OutputInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $output;

    /**
     * @var GitVersionControl|PHPUnit_Framework_MockObject_MockObject
     */
    protected $vcs;

    /**
     * @var HelperSet
     */
    protected $helperSet;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var AbstractFlowCommand
     */
    protected $command;

    /**
     * default setup of mock objects etc
     */
    public function setUp(): void
    {
        $this->output = $this->getMockBuilder(OutputInterface::class)->getMock();
        $this->input = $this->getMockBuilder(InputInterface::class)->getMock();
        $this->vcs = $this->getMockBuilder(GitVersionControl::class)->disableOriginalConstructor()->setMethods(
            [
                'executeCommands',
                'getBranches',
                'getCurrentBranch',
                'hasLocalModifications',
                'getTags',
                'revertWorkingCopy',
                'saveWorkingCopy',
            ]
        )->getMock();

        $commandClass = $this->getCommandClass();
        $this->command = new $commandClass();
        $this->command->setApplication($this->application);
        $this->command->setVersionControl($this->vcs);

        // create application with helper set and assign to command
        $this->helperSet = new HelperSet(
            [
                'formatter' => $this->getMockBuilder(FormatterHelper::class)->getMock(),
                'question' => $this->getMockBuilder(QuestionHelper::class)->getMock(),
            ]
        );
        $this->application = $this->getMockBuilder(Application::class)->setMethods(['getHelperSet'])->getMock();
        $this->application->method('getHelperSet')->willReturn($this->helperSet);
        $this->command->setApplication($this->application);
    }

    /**
     * @param array $options
     */
    protected function setOptionValues($options = []): void
    {
        $this->input->method('getOption')->willReturnCallback(
            static function ($option) use ($options) {
                return $options[$option] ?? false;
            }
        );
    }

    /**
     * @return GitVersionControl|MockObject
     */
    public function getUnallowAllCommandsVersionControl(): MockObject
    {
        $vcs = $this->getMockBuilder(GitVersionControl::class)->disableOriginalConstructor()->getMock();
        $vcs->method('canProcessCommand')->willReturn(false);
        return $vcs;
    }

    /**
     * @return string
     */
    abstract public function getCommandClass(): string;

    public function testReturnsErrorCodeWhenCommandIsNotCompatibleWithVersionControl(): void
    {
        $vcs = $this->getUnallowAllCommandsVersionControl();
        $vcs->expects($this->once())->method('canProcessCommand')->willReturn(false);

        // update command version control to use ;)
        $this->command->setVersionControl($vcs);

        $exitCode = $this->command->run($this->input, $this->output);
        $this->assertEquals(255, $exitCode);
    }
}
