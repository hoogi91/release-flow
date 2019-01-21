<?php

namespace Hoogi91\ReleaseFlow\Command;

use Hoogi91\ReleaseFlow\Exception\ReleaseFlowException;
use Hoogi91\ReleaseFlow\Service\FileProviderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This commands creates new patch version with dev flag and asks for confirmation
 *
 * @author Thorsten Hogenkamp <hoogi20@googlemail.com>
 */
class DevelopCommand extends AbstractFlowIncrementCommand
{

    /**
     * creates a release branch with version increment
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('develop')->setDescription('add dev flag to current version on develop branch');
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

        // get next patch version with dev flag and set it on file providers
        $newVersion = $this->getNextVersion(self::PATCH)->withPreRelease('dev');

        $output->writeln('<question>Note that this version will only be set by file providers and isn\'t add as tag to Version Control!</question>');
        if ($this->confirmNextVersion($newVersion) === true) {
            // execute version file providers to set flow version in additional files
            $fileProviderService = new FileProviderService($this->getApplication(), $this->getVersionControl());
            $fileProviderService->process($newVersion, $input->getOption('dry-run'));
            $fileProviderService->printResults($output);

            // commit changes to version control
            if ($this->getVersionControl()->hasLocalModifications() === true) {
                $this->getVersionControl()->saveWorkingCopy(sprintf(
                    'Bump version to %s',
                    $newVersion->getVersionString()
                ));
            }
        }
        return 0;
    }
}
