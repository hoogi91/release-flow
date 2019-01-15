<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception\ReleaseFlowException;
use Hoogi91\ReleaseFlow\VersionControl\AbstractVersionControl;
use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Version\Version;

/**
 * Base command
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
abstract class AbstractFlowCommand extends Command
{
    const MAJOR = 'major';
    const MINOR = 'minor';
    const PATCH = 'patch';

    /**
     * The version control
     *
     * @var AbstractVersionControl
     */
    protected $versionControl;

    /**
     * @return AbstractVersionControl
     */
    public function getVersionControl(): AbstractVersionControl
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

    /**
     * creates a release branch with version increment
     */
    protected function configure()
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'only print command without executing');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ReleaseFlowException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getVersionControl()->canProcessCommand(get_class($this)) === false) {
            $output->writeln(sprintf(
                '<error>%s could not be executed with version control %s</error>',
                get_class($this),
                get_class($this->versionControl)
            ));
            return 255;
        }

        if ($input->getOption('dry-run') !== false) {
            $this->getVersionControl()->setDryRun(true);

            /** @var FormatterHelper $formatter */
            $formatter = $this->getHelper('formatter');
            $output->writeln('');
            $output->writeln($formatter->formatBlock('Executing command with DRY-RUN', 'notice', true));
            $output->writeln('');
        }
        return 0;
    }
}
