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
        parent::configure();
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
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $newVersion = $this->getVersionControl()->getFlowVersion();
        if ($this->getVersionControl()->isHotfix()) {
            // create questions for hotfix branch
            $confirm = new ConfirmationQuestion(sprintf(
                '<question>Shall hotfix/%s be finished? [Y/n]</question>',
                $newVersion->getVersionString()
            ));
            $publish = new ConfirmationQuestion(
                '<question>Shall this hotfix be published automatically? [y/N]</question>',
                false
            );
        } else {
            // on default we create questions for a release branch
            $confirm = new ConfirmationQuestion(sprintf(
                '<question>Shall release/%s be finished? [Y/n]</question>',
                $newVersion->getVersionString()
            ));
            $publish = new ConfirmationQuestion(
                '<question>Shall this release be published automatically? [y/N]</question>',
                false
            );
        }

        if ($helper->ask($input, $output, $confirm)) {
            // execute version file providers to set flow version in additional files
            $this->executeVersionFileProviders($newVersion);

            // commit changes to version control before finishing hotfix
            $this->getVersionControl()->saveWorkingCopy(sprintf('Bump version to %s', $newVersion->getVersionString()));

            // finish hotfix and publish it if user accepts
            if ($this->getVersionControl()->isHotfix()) {
                $output->writeln($this->versionControl->finishHotfix($helper->ask($input, $output, $publish)));
            } else {
                $output->writeln($this->versionControl->finishRelease($helper->ask($input, $output, $publish)));
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
