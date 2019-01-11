<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
abstract class AbstractFlowCommand extends Command
{

    /**
     * The version control
     *
     * @var VersionControlInterface
     */
    protected $versionControl;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getVersionControl()->canProcessCommand(__CLASS__) === false) {
            $output->writeln(sprintf(
                '%s could not be executed with version control %s',
                __CLASS__,
                get_class($this->versionControl)
            ));
            return 255;
        }
        return 0;
    }

    /**
     * @return VersionControlInterface
     */
    public function getVersionControl(): VersionControlInterface
    {
        return $this->versionControl;
    }

    /**
     * Sets the version control to use
     *
     * @param VersionControlInterface $vcs
     */
    public function setVersionControl(VersionControlInterface $vcs)
    {
        $this->versionControl = $vcs;
    }
}
