<?php

namespace Hoogi91\ReleaseFlow\Service;

use Hoogi91\ReleaseFlow\Application;
use Hoogi91\ReleaseFlow\Exception\FileProviderException;
use Hoogi91\ReleaseFlow\Exception\ReleaseFlowException;
use Hoogi91\ReleaseFlow\FileProvider\ComposerFileProvider;
use Hoogi91\ReleaseFlow\FileProvider\FileProviderInterface;
use Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Version\Version;

/**
 * Class FileProviderService
 * @package Hoogi91\ReleaseFlow\Service
 */
class FileProviderService
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var VersionControlInterface
     */
    protected $versionControl;

    /**
     * @var array
     */
    protected $results = [];

    /**
     * FileProviderService constructor.
     *
     * @param Application $application
     * @param VersionControlInterface $versionControl
     */
    public function __construct(Application $application, VersionControlInterface $versionControl)
    {
        $this->application = $application;
        $this->versionControl = $versionControl;
    }


    /**
     * execute list of additional file providers
     *
     * @param Version $branchVersion
     * @param bool $dryRun
     *
     * @return static
     * @throws ReleaseFlowException
     */
    public function process(Version $branchVersion, bool $dryRun = false): self
    {
        try {
            // get provider list with composer file provider first
            $providers = [];
            $providers[] = ComposerFileProvider::class;

            // get provider classes from composer
            $additionalProviders = $this->application->getComposer()->getProviderClasses();
            if (!empty($additionalProviders)) {
                array_push($providers, ...$additionalProviders);
            }

            if (empty($providers)) {
                return $this;
            }

            // iterate all providers that implement provider interface
            foreach (array_unique($providers) as $provider) {
                if (class_exists($provider) === false ||
                    in_array(FileProviderInterface::class, class_implements($provider), true) === false) {
                    continue;
                }

                // save provider result (has done something or not) in result
                /** @var FileProviderInterface $providerInstance */
                $providerInstance = new $provider();
                if ($dryRun !== false) {
                    $providerInstance->enableDryRun();
                }
                $this->results[$provider] = $providerInstance->process($branchVersion, $this->application);
            }
            return $this;
        } catch (FileProviderException $exception) {
            // revert working copy
            $this->versionControl->revertWorkingCopy();

            // throw new exception to break processing
            throw new ReleaseFlowException(
                'Executing Version File Providers failed! All changes of file providers were reverted.',
                1547566637,
                $exception
            );
        }
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param OutputInterface $output
     */
    public function printResults(OutputInterface $output): void
    {
        if (empty($this->results)) {
            $output->writeln('');
            $output->writeln((new FormatterHelper())->formatBlock('No Results', 'error', true));
            $output->writeln('');
            return;
        }

        // print which file providers have been executed before bumping version
        $messages = array_map(
            static function ($providerClass, $success) {
                return sprintf('  [%s] %s', ($success ? 'CHANGE' : 'SKIP'), $providerClass);
            },
            array_keys($this->results),
            $this->results
        );

        // add short explanation message before list
        array_unshift(
            $messages,
            'The following file providers have been executed and may have changed some files:'
        );

        // print status of executed and not executed file providers
        $output->writeln('');
        $output->writeln((new FormatterHelper())->formatBlock($messages, 'notice', true));
        $output->writeln('');
    }
}
