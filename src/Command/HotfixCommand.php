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
class HotfixCommand extends AbstractFlowIncrementCommand
{
    /**
     * creates a hotfix branch with a patch version increment
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName('hotfix')->setAliases(
            [
                'hot',
                'fix',
            ]
        )->setDescription('creates a hotfix branch with a patch version increment');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        // get next patch version and ask user if new version is correctly set
        $nextVersion = $this->getNextVersion(self::PATCH);

        if ($this->confirmNextVersion($nextVersion) === true) {
            // start version control hotfix branch if confirmed
            $output->writeln($this->versionControl->startHotfix($nextVersion));
        }
        return 0;
    }
}
