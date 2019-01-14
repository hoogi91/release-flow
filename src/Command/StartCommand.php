<?php

namespace Hoogi91\ReleaseFlow\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Version\Version;

/**
 * This commands asks for the version increment and then creates a regular release branch.
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class StartCommand extends AbstractFlowCommand
{
    const MAJOR = 'major';
    const MINOR = 'minor';
    const PATCH = 'patch';

    /**
     * creates a release branch with version increment
     */
    protected function configure()
    {
        $this->setName('start')->setDescription('creates a release branch with version increment');
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

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion('Please enter the version increment for your next release:', [
            self::MAJOR => '1.x.y => 2.0.0 (MUST on backwards <comment>incompatible</comment> changes)',
            self::MINOR => '1.2.x => 1.3.0 (MUST on new backwards compatible functionality or public functionality marked as deprecated. MAY on substantial new functionality or improvements)',
            self::PATCH => '1.2.3 => 1.2.4 (MUST if only backwards compatible bug fixes introduced)',
        ]);
        $question->setErrorMessage('Option %s is invalid.');

        // get next version by increment option set from user input and inform about the next version string
        $incrementType = $helper->ask($input, $output, $question);
        $nextVersion = $this->getNextVersion($incrementType);
        $output->writeln('<info>The next version will be ' . $nextVersion . '.</info>');

        // create release or hotfix branch for new version in version control
        $this->versionControl->startRelease($nextVersion);
        return 0;
    }

    /**
     * @param string $incrementType
     *
     * @return Version
     */
    protected function getNextVersion(string $incrementType = self::PATCH)
    {
        $currentVersion = $this->versionControl->getCurrentVersion();
        if ($incrementType === self::MAJOR) {
            return $currentVersion->incrementMajor();
        } elseif ($incrementType === self::MINOR) {
            return $currentVersion->incrementMinor();
        }
        return $currentVersion->incrementPatch();
    }

}
