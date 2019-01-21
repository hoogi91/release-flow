<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception\ReleaseFlowException;
use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var InputInterface
     */
    protected $input;

    /**
     * The version control
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * The version control
     *
     * @var VersionControlInterface
     */
    protected $versionControl;

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
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
        $this->input = $input;
        $this->output = $output;

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
