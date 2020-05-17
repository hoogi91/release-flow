<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command\GitFlow;

use Hoogi91\ReleaseFlow\VersionControl\GitFlowVersionControl;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class FinishCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command\GitFlow
 */
class FinishCommandTest extends \Hoogi91\ReleaseFlow\Tests\Unit\Command\FinishCommandTest
{

    /**
     * @var GitFlowVersionControl|PHPUnit_Framework_MockObject_MockObject
     */
    protected $vcs;

    /**
     * default setup of mock objects etc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->vcs = $this->getMockBuilder(GitFlowVersionControl::class)->disableOriginalConstructor()->setMethods(
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
        $this->command->setVersionControl($this->vcs);
    }

    public function validateFinishReleaseCommands($commands): string
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git flow release finish -p -m "Tagging version 1.0.3" 1.0.3', $commands[0]);

        return '';
    }

    public function validateFinishHotfixCommands($commands): string
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git flow hotfix finish -p -m "Tagging version 1.0.3" 1.0.3', $commands[0]);

        return '';
    }
}
