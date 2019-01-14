<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
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
        parent::configure();
        $this->setName('hotfix')->setDescription('creates a hotfix branch with a patch version increment');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // get next patch version and ask user if new version is correctly set
        $nextVersion = $this->getNextVersion(self::PATCH);
        if ($helper->ask($input, $output, $this->getNextVersionConfirmation($nextVersion))) {
            // start version control hotfix branch if confirmed
            $output->writeln($this->versionControl->startHotfix($nextVersion));
        }
        return 0;
    }
}
