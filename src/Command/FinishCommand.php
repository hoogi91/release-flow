<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Exception\FileProviderException;
use Hoogi91\ReleaseFlow\Exception\ReleaseFlowException;
use Hoogi91\ReleaseFlow\FileProvider\ComposerFileProvider;
use Hoogi91\ReleaseFlow\FileProvider\FileProviderInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
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
     * @throws ReleaseFlowException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        // check if repository has local modifications that needs to be committed first
        if ($this->getVersionControl()->hasLocalModifications() === true) {
            $saveModifications = new ConfirmationQuestion('<question>You have uncommitted changes in your working copy that needs to be committed first! Do you want to proceed? [Y/n]</question>');
            if ($helper->ask($input, $output, $saveModifications) === false) {
                return 0;
            }

            // get commit message with confirmation dialog and save working copy
            $this->getVersionControl()->saveWorkingCopy($this->getCommitMessage($input, $output));
        }

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
            $fileProviderResults = $this->executeVersionFileProviders($newVersion, $input->getOption('dry-run'));

            // print which file providers have been executed before bumping version
            if (!empty($fileProviderResults)) {
                $messages = array_map(function ($providerClass, $success) {
                    return sprintf('  [%s] %s', ($success ? 'CHANGE' : 'SKIP'), $providerClass);
                }, array_keys($fileProviderResults), $fileProviderResults);

                // add short explanation message before list
                array_unshift(
                    $messages,
                    'The following file providers have been executed and may have changed some files:'
                );

                // print status of executed and not executed file providers
                $output->writeln('');
                $output->writeln($formatter->formatBlock($messages, 'notice', true));
                $output->writeln('');
            }

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function getCommitMessage($input, $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // get commit message from user
        $commitMessage = $helper->ask($input, $output, new Question('Please enter commit message:'));
        $confirmCommitMessage = new ConfirmationQuestion(sprintf(
            'Please confirm commit message "%s" [y/N]',
            $commitMessage
        ), false);

        if ($helper->ask($input, $output, $confirmCommitMessage) === false) {
            return $this->getCommitMessage($input, $output);
        }
        return $commitMessage;
    }

    /**
     * execute list of additional file providers
     *
     * @param Version $branchVersion
     * @param bool    $dryRun
     *
     * @return array
     * @throws ReleaseFlowException
     */
    protected function executeVersionFileProviders(Version $branchVersion, bool $dryRun = false)
    {
        try {
            /** @var Application $application */
            $application = $this->getApplication();

            // get provider list with composer file provider first
            $providers = [];
            $providers[] = ComposerFileProvider::class;
            array_push($providers, ...$application->getComposer()->getProviderClasses());
            if (empty($providers)) {
                return [];
            }

            // iterate all providers that implement provider interface
            $results = [];
            foreach (array_unique($providers) as $provider) {
                if (
                    class_exists($provider) === false ||
                    in_array(FileProviderInterface::class, class_implements($provider)) === false
                ) {
                    continue;
                }

                // save provider result (has done something or not) in result
                /** @var FileProviderInterface $providerInstance */
                $providerInstance = new $provider();
                if ($dryRun !== false) {
                    $providerInstance->enableDryRun();
                }
                $results[$provider] = $providerInstance->process($branchVersion, $this->getApplication());
            }
            return $results;
        } catch (FileProviderException $exception) {
            // revert working copy
            $this->getVersionControl()->revertWorkingCopy();

            // throw new exception to break processing
            throw new ReleaseFlowException(
                'Executing Version File Providers failed! All changes of file providers were reverted.',
                1547566637,
                $exception
            );
        }
    }
}
