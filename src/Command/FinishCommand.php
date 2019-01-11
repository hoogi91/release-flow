<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Version\Version;

/**
 * This commands finishes a regular release or hotfix branch
 * and updates version numbers in additional project files if registered
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class FinishCommand extends AbstractFlowCommand
{

    /**
     * finishes a regular release or hotfix branch
     * and updates version numbers in additional project files if registered
     */
    protected function configure()
    {
        $this->setName('finish')->setDescription('finish release or hotfix branch and create new tag.');
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
        parent::execute($input, $output);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        if ($this->getVersionControl()->isHotfix()) {
            // create questions for hotfix branch
            $confirm = new ConfirmationQuestion('Please confirm to finish this hotfix.');
            $publish = new ConfirmationQuestion('Shall this hotfix be published automatically?', false);
        } else {
            // on default we create questions for a release branch
            $confirm = new ConfirmationQuestion('Please confirm to finish this release.');
            $publish = new ConfirmationQuestion('Shall this release be published automatically?', false);
        }

        if ($helper->ask($input, $output, $confirm)) {
            // execute version file providers to set flow version in additional files
            $this->executeVersionFileProviders($this->getVersionControl()->getFlowVersion());

            // commit changes to version control before finishing hotfix
            $this->getVersionControl()->saveWorkingCopy(sprintf(
                'Bump version to %s',
                $this->getVersionControl()->getFlowVersion()->getVersionString()
            ));

            // finish hotfix and publish it if user accepts
            if ($this->getVersionControl()->isHotfix()) {
                $this->versionControl->finishHotfix($helper->ask($input, $output, $publish));
            } else {
                $this->versionControl->finishRelease($helper->ask($input, $output, $publish));
            }
        }

        return 0;
    }

    /**
     * TODO: implement provider option and execute them here
     *
     * @param Version $branchVersion
     */
    protected function executeVersionFileProviders(Version $branchVersion)
    {

    }
}
