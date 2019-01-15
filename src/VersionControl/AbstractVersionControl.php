<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Hoogi91\ReleaseFlow\Utility\LogUtility;
use Version\Exception\InvalidVersionStringException;
use Version\Version;

/**
 * Class AbstractVersionControl
 * @package Hoogi91\ReleaseFlow\VersionControl
 */
abstract class AbstractVersionControl implements VersionControlInterface
{
    /**
     * master identifying string in branch name
     * @var string
     */
    const MASTER = 'master';

    /**
     * develop identifying string in branch name
     * @var string
     */
    const DEVELOP = 'develop';

    /**
     * release identifying string in branch name
     * @var string
     */
    const RELEASE = 'release';

    /**
     * hotfix identifying string in branch name
     * @var string
     */
    const HOTFIX = 'hotfix';

    /**
     * list of allowed commands by version control
     */
    const ALLOWED_COMMANDS = [
        StartCommand::class,
        HotfixCommand::class,
        FinishCommand::class,
    ];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * dry-run: do nothing
     * @var boolean
     */
    protected $dryRun = false;

    /**
     * @param string $workingDirectory
     */
    public function setWorkingDirectory(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @param boolean $flag
     */
    public function setDryRun(bool $flag)
    {
        $this->dryRun = $flag;
    }

    /**
     * @param string $command
     *
     * @return bool
     * @throws VersionControlException
     */
    public function canProcessCommand(string $command)
    {
        $canProcess = in_array($command, static::ALLOWED_COMMANDS);
        if ($canProcess !== true) {
            return false;
        }

        // evaluate release and hotfix branches
        $releaseOrHotfixBranch = array_values(array_filter($this->getBranches(), function ($branch) {
            return strpos($branch, static::RELEASE) === 0 || strpos($branch, static::HOTFIX) === 0;
        }));

        if ($command === FinishCommand::class) {
            if (empty($releaseOrHotfixBranch)) {
                // release or hotfix hasn't been started yet
                throw new VersionControlException('You currently do not have a active flow release/hotfix branch to finish');
            } elseif (!in_array($this->getCurrentBranch(), $releaseOrHotfixBranch)) {
                // current branch isn't the release or hotfix branch => switch before execute
                throw new VersionControlException(sprintf(
                    'You are not in a flow release/hotfix branch (current: %s). Please switch into a flow branch: %s',
                    $this->getCurrentBranch(),
                    implode(', ', $releaseOrHotfixBranch)
                ));
            }
        } elseif (in_array($command, [StartCommand::class, HotfixCommand::class])) {
            if (in_array($this->getCurrentBranch(), $releaseOrHotfixBranch)) {
                // already in release or hotfix branch
                throw new VersionControlException(sprintf(
                    'You are already in flow branch: %s',
                    $this->getCurrentBranch()
                ));
            } elseif (!empty($releaseOrHotfixBranch)) {
                // hotfix or release branch already exists so cancel creation
                throw new VersionControlException(sprintf(
                    'You already have active flow branch(es) to finish first: %s',
                    implode(', ', $releaseOrHotfixBranch)
                ));
            }
        }
        return true;
    }

    /**
     * Returns the highest valid version tag.
     *
     * @return Version
     */
    public function getCurrentVersion()
    {
        $tags = $this->getTags();
        if (count($tags) === 0) {
            return Version::fromString('0.0.1');
        }

        usort($tags, function ($v1, $v2) {
            /** @var $v1 Version */
            /** @var $v2 Version */
            if ($v1->isEqualTo($v2)) {
                return 0;
            }
            return $v1->isGreaterThan($v2) ? 1 : -1;
        });
        return array_pop($tags);
    }

    /**
     * Return if current branch is release branch
     *
     * @return boolean
     */
    public function isRelease()
    {
        return strpos($this->getCurrentBranch(), static::RELEASE . '/') === 0;
    }

    /**
     * Return if current branch is hotfix branch
     *
     * @return boolean
     */
    public function isHotfix()
    {
        return strpos($this->getCurrentBranch(), static::HOTFIX . '/') === 0;
    }

    /**
     * Checks if the current branch is a release or hotfix branch.
     *
     * @return boolean
     */
    protected function isInTheFlow()
    {
        return $this->isRelease() || $this->isHotfix();
    }

    /**
     * Returns the current flow version number => see getFlowBranch if branch is release or hotfix
     *
     * @return Version
     * @throws VersionControlException
     */
    public function getFlowVersion()
    {
        if (!$this->isInTheFlow()) {
            throw new VersionControlException('Flow version can\'t be determined cause no flow is started!');
        }

        // get current branch name => will start with release or hotfix
        $branch = $this->getCurrentBranch();

        try {
            // try to get release number from hotfix or release
            if ($this->isHotfix()) {
                return Version::fromString(str_replace(static::HOTFIX . '/', '', $branch));
            }
            return Version::fromString(str_replace(static::RELEASE . '/', '', $branch));
        } catch (InvalidVersionStringException $e) {
            LogUtility::warning(sprintf(
                'Version Number of branch %s couldn\'t be formatted to class %s',
                $branch,
                Version::class
            ));
            throw new VersionControlException('Cannot detect version in branch name: ' . $branch);
        }
    }

    /**
     * Start a git flow release.
     *
     * @param Version $version
     *
     * @return string
     */
    public function startRelease(Version $version)
    {
        return $this->executeCommands($this->getStartCommands($version, static::RELEASE));
    }

    /**
     * Finishes the current git flow release without tagging.
     *
     * @param boolean $publish
     *
     * @return string
     * @throws VersionControlException
     */
    public function finishRelease(bool $publish = false)
    {
        if ($this->isRelease() === false) {
            throw new VersionControlException('Can\'t finish release if current branch isn\'t the release branch');
        }
        return $this->executeCommands($this->getFinishCommands($this->getFlowVersion(), static::RELEASE, $publish));
    }

    /**
     * Start a git flow hotfix.
     *
     * @param Version $version
     *
     * @return string
     */
    public function startHotfix(Version $version)
    {
        return $this->executeCommands($this->getStartCommands($version, static::HOTFIX));
    }

    /**
     * Finishes the current git flow hotfix without tagging.
     *
     * @param boolean $publish
     *
     * @return string
     * @throws VersionControlException
     */
    public function finishHotfix(bool $publish = false)
    {
        if ($this->isHotfix() === false) {
            throw new VersionControlException('Can\'t finish hotfix if current branch isn\'t the hotfix branch');
        }
        return $this->executeCommands($this->getFinishCommands($this->getFlowVersion(), static::HOTFIX, $publish));
    }

    /**
     * Get the command line string that will be an argument of executeCommand and start the version control flow
     *
     * @param Version $version
     * @param string  $branchType
     *
     * @return string[]
     */
    abstract protected function getStartCommands(
        Version $version,
        string $branchType = self::RELEASE
    );

    /**
     * Get the command line string that will be an argument of executeCommand and finish the version control flow
     *
     * @param Version $version
     * @param string  $branchType
     * @param boolean $publish
     *
     * @return string[]
     */
    abstract protected function getFinishCommands(
        Version $version,
        string $branchType = self::RELEASE,
        bool $publish = false
    );

    /**
     * Will execute given command and return command's output
     *
     * @param array $commands
     *
     * @return string
     */
    protected function executeCommands(array $commands)
    {
        if (empty($commands)) {
            return '<error>No command given! Nothing to do...</error>';
        }

        if ($this->dryRun !== false) {
            return sprintf(
                "<error>DRY-RUN</error> <info>the following commands will be executed:</info>\n<fg=cyan>> %s</>",
                implode(PHP_EOL . '> ', $commands)
            );
        }

        $output = null;
        foreach ($commands as $command) {
            system($command, $var);
        }
        return $output;
    }
}