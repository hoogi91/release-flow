<?php

namespace Hoogi91\ReleaseFlow\Tests\Unit\Command;

use Hoogi91\ReleaseFlow\Command\StatusCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Version\Version;

/**
 * Class StatusCommandTest
 * @package Hoogi91\ReleaseFlow\Tests\Unit\Command
 */
class StatusCommandTest extends CommandTest
{

    /**
     * setup command class
     */
    public function getCommandClass()
    {
        return StatusCommand::class;
    }

    /**
     * @test
     */
    public function testSuccessfulExecution()
    {
        $this->vcs->expects($this->once())->method('getTags')->willReturn([
            Version::fromString('1.0.0'),
            Version::fromString('2.0.0'),
            Version::fromString('2.1.0'),
            Version::fromString('2.2.0'),
            Version::fromString('2.3.0'),
        ]);
        $this->vcs->expects($this->exactly(2))->method('getBranches')->willReturn([
            'develop',
            'master',
            'release/2.3.1',
        ]);
        $this->vcs->expects($this->exactly(1))->method('getCurrentBranch')->willReturn('develop');

        /** @var FormatterHelper|\PHPUnit_Framework_MockObject_MockObject $formatter */
        $formatter = $this->helperSet->get('formatter');
        $formatter->expects($this->any())
            ->method('formatBlock')
            ->with($this->callback(array($this, 'validateMessages')));

        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }

    public function validateMessages($messages)
    {
        if (is_string($messages)) {
            $this->assertNotEmpty($messages);
        } elseif (is_array($messages)) {
            $this->assertEquals('Current Branch: develop (2.3.0)', $messages[0]);
            $this->assertEquals('Flow Branches: release/2.3.1', $messages[1]);
            $this->assertEquals('Next Version (dev): 2.3.1-dev', $messages[2]);
            $this->assertEquals('Next Version (patch): 2.3.1', $messages[3]);
            $this->assertEquals('Next Version (minor): 2.4.0', $messages[4]);
            $this->assertEquals('Next Version (major): 3.0.0', $messages[5]);
        } else {
            // unexpected internal type of messages
            return false;
        }
        return true;
    }
}