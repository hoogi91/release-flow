<?php

namespace Hoogi91\ReleaseFlow;

use Hoogi91\ReleaseFlow\Command\AbstractFlowCommand;
use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\VersionControl\GitVersionControl;
use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The console application.
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * version control interface to interact with
     *
     * @var VersionControlInterface
     */
    protected $vcs;

    /**
     * Application constructor.
     *
     * @param string $name
     * @param string $version
     */
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        // TODO: make used version control configurable
        $this->vcs = new GitVersionControl(getcwd());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Exception
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->addCommand(StartCommand::class);
        $this->addCommand(HotfixCommand::class);
        $this->addCommand(FinishCommand::class);
        return parent::run($input, $output);
    }

    /**
     * Creates and adds a new command.
     *
     * @param string $name command class name
     */
    private function addCommand($name)
    {
        /** @var AbstractFlowCommand $command */
        $command = new $name();
        $command->setVersionControl($this->vcs);
        $this->add($command);
    }
}
