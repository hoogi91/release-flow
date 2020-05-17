<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\VersionControl\AbstractVersionControl;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This commands returns status information of current repository flow
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 */
class StatusCommand extends AbstractFlowCommand
{

    /**
     * creates a release branch with version increment
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName('status')->setDescription('get current repository flow status');
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

        $releaseOrHotfixBranch = array_values(
            array_filter(
                $this->getVersionControl()->getBranches(),
                static function ($branch) {
                    return strpos($branch, AbstractVersionControl::RELEASE) === 0
                        || strpos($branch, AbstractVersionControl::HOTFIX) === 0;
                }
            )
        );
        $currentVersion = $this->getVersionControl()->getCurrentVersion();

        $messages = [];
        $messages[] = vsprintf(
            'Current Branch: %s (%s)',
            [
                $this->getVersionControl()->getCurrentBranch(),
                $currentVersion,
            ]
        );
        $messages[] = sprintf(
            'Flow Branches: %s',
            $releaseOrHotfixBranch ? implode(', ', $releaseOrHotfixBranch) : 'none'
        );
        $messages[] = 'Next Version (dev): ' . $currentVersion->incrementPatch()->withPreRelease('dev');
        $messages[] = 'Next Version (patch): ' . $currentVersion->incrementPatch();
        $messages[] = 'Next Version (minor): ' . $currentVersion->incrementMinor();
        $messages[] = 'Next Version (major): ' . $currentVersion->incrementMajor();


        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');
        $output->writeln($formatter->formatBlock($messages, 'info', true));
        return 0;
    }
}
