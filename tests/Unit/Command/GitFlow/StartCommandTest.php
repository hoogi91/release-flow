<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command\GitFlow;

use Hoogi91\ReleaseFlow\VersionControl\GitFlowVersionControl;

/**
 * Class StartCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command\GitFlow
 */
class StartCommandTest extends \Hoogi91\ReleaseFlow\Tests\Unit\Command\StartCommandTest
{

    /**
     * @var GitFlowVersionControl|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $vcs;

    /**
     * default setup of mock objects etc
     */
    public function setUp()
    {
        parent::setUp();
        $this->vcs = $this->getMockBuilder(GitFlowVersionControl::class)->disableOriginalConstructor()->setMethods([
            'executeCommands',
            'getBranches',
            'getCurrentBranch',
            'hasLocalModifications',
            'getTags',
            'revertWorkingCopy',
            'saveWorkingCopy',
        ])->getMock();
        $this->command->setVersionControl($this->vcs);
    }

    public function validateStartMinorReleaseCommands($commands)
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git flow release start 2.4.0', $commands[0]);
    }

    public function validateStartMajorReleaseCommands($commands)
    {
        $this->assertInternalType('array', $commands);
        $this->assertCount(1, $commands);

        // check if commands are in correct order and have no issues
        $this->assertEquals('git flow release start 3.0.0', $commands[0]);
    }
}