<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Utility\LogUtility;
use TQ\Git\Repository\Repository;
use TQ\Vcs\Cli\CallException;
use TQ\Vcs\Cli\CallResult;
use Version\Exception\InvalidVersionStringException;
use Version\Version;

/**
 * GitVersionControl implementation of the VersionControlInterface
 *
 * Is a bridge to PHPGit\Git.
 *
 * @link https://github.com/kzykhys/PHPGit
 */
class GitVersionControl extends AbstractVersionControl
{
    /**
     * git command line client
     *
     * @var Repository
     */
    protected $git;

    /**
     * GitVersionControl constructor.
     *
     * @param string $cwd
     */
    public function __construct(string $cwd)
    {
        $this->setWorkingDirectory($cwd);
        $this->git = Repository::open($this->workingDirectory);
    }

    /**
     * @return array
     */
    public function getBranches()
    {
        return $this->git->getBranches();
    }

    /**
     * @return string
     */
    public function getCurrentBranch()
    {
        return $this->git->getCurrentBranch();
    }

    /**
     * @return bool
     */
    public function hasLocalModifications()
    {
        return $this->git->isDirty();
    }

    /**
     * @return Version[]
     */
    public function getTags()
    {
        /** @var CallResult $result */
        $result = $this->git->getGit()->{'tag'}($this->workingDirectory, []);
        $tags = array_map('trim', explode(PHP_EOL, $result->getStdOut()));
        if (empty($tags)) {
            return [];
        }

        return array_filter(array_map(function ($versionNumber) {
            try {
                return Version::fromString($versionNumber);
            } catch (InvalidVersionStringException $e) {
                LogUtility::warning(sprintf(
                    'Version Number %s couldn\'t be formatted to class %s',
                    $versionNumber,
                    Version::class
                ));
                return null;
            }
        }, $tags));
    }

    /**
     * @param string $commitMsg
     *
     * @return bool
     */
    public function saveWorkingCopy(string $commitMsg = '')
    {
        try {
            if ($this->dryRun === false) {
                $this->git->add();
                $this->git->commit($commitMsg);
            }
            return true;
        } catch (CallException $e) {
            LogUtility::error(sprintf('Working Copy couldn\'t be saved: %s', $e->getMessage()));
            return false;
        }
    }

    /**
     * Get the command line string that will be an argument of executeCommand and start the version control flow
     *
     * @param Version $version
     * @param string  $branchType
     *
     * @return string[]
     */
    protected function getStartCommands(Version $version, string $branchType = self::RELEASE)
    {
        if ($branchType === self::RELEASE) {
            return [sprintf('git checkout -b release/%s %s', $version->getVersionString(), self::DEVELOP)];
        } elseif ($branchType === self::HOTFIX) {
            return [sprintf('git checkout -b hotfix/%s %s', $version->getVersionString(), self::MASTER)];
        }
        return [];
    }

    /**
     * Get the command line string that will be an argument of executeCommand and finish the version control flow
     *
     * @param Version $version
     * @param string  $branchType
     * @param boolean $publish
     *
     * @return string[]
     */
    protected function getFinishCommands(Version $version, string $branchType = self::RELEASE, bool $publish = false)
    {
        if ($branchType === self::RELEASE) {
            return array_filter([
                sprintf('git checkout %s', self::MASTER),
                sprintf('git merge --no-ff release/%s', $version->getVersionString()),
                sprintf('git tag -a %1$s -m "Tagging version %1$s"', $version->getVersionString()),
                ($publish ? 'git push origin --tags' : ''),
                sprintf('git checkout %s', self::DEVELOP),
                sprintf('git merge --no-ff release/%s', $version->getVersionString()),
                sprintf('git branch -d release/%s', $version->getVersionString()),
            ]);
        } elseif ($branchType === self::HOTFIX) {
            return [
                sprintf('git checkout %s', self::MASTER),
                sprintf('git merge --no-ff hotfix/%s', $version->getVersionString()),
                sprintf('git tag -a %1$s -m "Tagging version %1$s"', $version->getVersionString()),
                ($publish ? 'git push origin --tags' : ''),
                sprintf('git checkout %s', self::DEVELOP),
                sprintf('git merge --no-ff hotfix/%s', $version->getVersionString()),
                sprintf('git branch -d hotfix/%s', $version->getVersionString()),
            ];
        }
        return [];
    }
}
