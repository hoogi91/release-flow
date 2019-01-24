<?php

namespace Hoogi91\ReleaseFlow\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Version\Version;

/**
 * Base command for version increment jobs
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 */
abstract class AbstractFlowIncrementCommand extends AbstractFlowCommand
{

    /**
     * creates a release branch with version increment
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'force creation of next version and do not ask for confirmation'
        );
    }

    /**
     * @param Version $version
     *
     * @return bool
     */
    protected function confirmNextVersion(Version $version)
    {
        // check if confirmation is forced and needs to be skipped
        if ($this->getInput()->getOption('force') === true) {
            return true;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper->ask($this->getInput(), $this->getOutput(), $this->getNextVersionConfirmation($version));
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

    /**
     * @param Version|string $incrementTypeOrVersion
     *
     * @return ConfirmationQuestion
     */
    protected function getNextVersionConfirmation($incrementTypeOrVersion = self::PATCH)
    {
        // allow increment type or version object
        $version = $incrementTypeOrVersion;
        if (!$incrementTypeOrVersion instanceof Version) {
            $version = $this->getNextVersion($incrementTypeOrVersion);
        }
        return new ConfirmationQuestion(sprintf(
            '<question>The next version will be %s. Do you agree? [Y/n]</question>',
            $version->getVersionString()
        ));
    }
}
