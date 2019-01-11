<?php

namespace Hoogi91\ReleaseFlow\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This commands creates a hotfix branch with a patch version increment.
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class HotfixCommand extends AbstractFlowCommand
{
    /**
     * creates a hotfix branch with a patch version increment
     */
    protected function configure()
    {
        $this->setName('hotfix')->setDescription('creates a hotfix branch with a patch version increment');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // find current and next version
        $currentVersion = $this->versionControl->getCurrentVersion();
        $nextVersion = $currentVersion->incrementPatch();

        // print info of the new version
        $output->writeln(sprintf(
            '<info>The next version will be %s.</info>',
            $nextVersion->getVersionString()
        ));

        // start version control hotfix branch
        $this->versionControl->startHotfix($nextVersion);
        return 0;
    }
}
