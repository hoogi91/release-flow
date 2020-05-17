<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Command\DevelopCommand;
use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\Command\StatusCommand;
use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Hoogi91\ReleaseFlow\Utility\LogUtility;
use Symfony\Component\Console\Output\OutputInterface;
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
    public const MASTER = 'master';

    /**
     * develop identifying string in branch name
     * @var string
     */
    public const DEVELOP = 'develop';

    /**
     * release identifying string in branch name
     * @var string
     */
    public const RELEASE = 'release';

    /**
     * hotfix identifying string in branch name
     * @var string
     */
    public const HOTFIX = 'hotfix';

    /**
     * list of allowed commands by version control
     */
    private const ALLOWED_COMMANDS = [
        StatusCommand::class,
        StartCommand::class,
        HotfixCommand::class,
        DevelopCommand::class,
        FinishCommand::class,
    ];

    /**
     * @var string
     */
    protected $workingDirectory;

    /**
     * dry-run: do nothing
     * @var OutputInterface|null
     */
    protected $dryRun;

    /**
     * @param string $workingDirectory
     */
    public function setWorkingDirectory(string $workingDirectory): void
    {
        $this->workingDirectory = $workingDirectory;
    }

    /**
     * @param OutputInterface $output
     */
    public function setDryRun(?OutputInterface $output): void
    {
        $this->dryRun = $output;
    }

    /**
     * Return array list of all branches
     *
     * @return array
     */
    abstract public function getBranches(): array;

    /**
     * Return the current branch
     *
     * @return string
     */
    abstract public function getCurrentBranch(): string;

    /**
     * Return all tags of the project
     *
     * @return Version[]
     */
    abstract public function getTags(): array;

    /**
     * @param string $command
     *
     * @return bool
     * @throws VersionControlException
     */
    public function canProcessCommand(string $command): bool
    {
        $canProcess = in_array($command, self::ALLOWED_COMMANDS);
        if ($canProcess !== true) {
            return false;
        }

        // evaluate release and hotfix branches
        $releaseOrHotfixBranch = array_values(
            array_filter(
                $this->getBranches(),
                static function ($branch) {
                    return strpos($branch, static::RELEASE) === 0 || strpos($branch, static::HOTFIX) === 0;
                }
            )
        );

        if ($command === FinishCommand::class) {
            if (empty($releaseOrHotfixBranch)) {
                // release or hotfix hasn't been started yet
                throw new VersionControlException(
                    'You currently do not have a active flow release/hotfix branch to finish'
                );
            }

            if (!in_array($this->getCurrentBranch(), $releaseOrHotfixBranch, true)) {
                // current branch isn't the release or hotfix branch => switch before execute
                throw new VersionControlException(
                    sprintf(
                        'You are not in a flow release/hotfix branch (current: %s).'
                        . ' Please switch into a flow branch: %s',
                        $this->getCurrentBranch(),
                        implode(', ', $releaseOrHotfixBranch)
                    )
                );
            }
        } elseif ($command === DevelopCommand::class) {
            if ($this->getCurrentBranch() !== 'develop') {
                // you need to be in develop branch
                throw new VersionControlException(
                    sprintf(
                        'You need to be in develop branch but current branch is: %s',
                        $this->getCurrentBranch()
                    )
                );
            }
        } elseif (in_array($command, [StartCommand::class, HotfixCommand::class])) {
            if (in_array($this->getCurrentBranch(), $releaseOrHotfixBranch, true)) {
                // already in release or hotfix branch
                throw new VersionControlException(
                    sprintf(
                        'You are already in flow branch: %s',
                        $this->getCurrentBranch()
                    )
                );
            }

            if (!empty($releaseOrHotfixBranch)) {
                // hotfix or release branch already exists so cancel creation
                throw new VersionControlException(
                    sprintf(
                        'You already have active flow branch(es) to finish first: %s',
                        implode(', ', $releaseOrHotfixBranch)
                    )
                );
            }
        }
        return true;
    }

    /**
     * Returns the highest valid version tag.
     *
     * @return Version
     */
    public function getCurrentVersion(): Version
    {
        $tags = $this->getTags();
        if (count($tags) === 0) {
            return Version::fromString('0.0.1');
        }

        usort(
            $tags,
            static function ($v1, $v2) {
                /** @var $v1 Version */
                /** @var $v2 Version */
                if ($v1->isEqualTo($v2)) {
                    return 0;
                }
                return $v1->isGreaterThan($v2) ? 1 : -1;
            }
        );
        return array_pop($tags);
    }

    /**
     * Return if current branch is release branch
     *
     * @return bool
     */
    public function isRelease(): bool
    {
        return strpos($this->getCurrentBranch(), static::RELEASE . '/') === 0;
    }

    /**
     * Return if current branch is hotfix branch
     *
     * @return bool
     */
    public function isHotfix(): bool
    {
        return strpos($this->getCurrentBranch(), static::HOTFIX . '/') === 0;
    }

    /**
     * Checks if the current branch is a release or hotfix branch.
     *
     * @return bool
     */
    protected function isInTheFlow(): bool
    {
        return $this->isRelease() || $this->isHotfix();
    }

    /**
     * Returns the current flow version number => see getFlowBranch if branch is release or hotfix
     *
     * @return Version
     * @throws VersionControlException
     */
    public function getFlowVersion(): Version
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
            LogUtility::warning(
                sprintf(
                    'Version Number of branch %s couldn\'t be formatted to class %s',
                    $branch,
                    Version::class
                )
            );
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
    public function startRelease(Version $version): string
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
    public function finishRelease(bool $publish = false): string
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
    public function startHotfix(Version $version): string
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
    public function finishHotfix(bool $publish = false): string
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
     * @param string $branchType
     *
     * @return string[]
     */
    abstract protected function getStartCommands(
        Version $version,
        string $branchType = self::RELEASE
    ): array;

    /**
     * Get the command line string that will be an argument of executeCommand and finish the version control flow
     *
     * @param Version $version
     * @param string $branchType
     * @param boolean $publish
     *
     * @return string[]
     */
    abstract protected function getFinishCommands(
        Version $version,
        string $branchType = self::RELEASE,
        bool $publish = false
    ): array;

    /**
     * Will execute given command and return command's output
     *
     * @param array $commands
     *
     * @return string
     */
    protected function executeCommands(array $commands): string
    {
        if (empty($commands)) {
            return '<error>No command given! Nothing to do...</error>';
        }

        if ($this->dryRun instanceof OutputInterface) {
            return sprintf(
                "<error>DRY-RUN</error> <info>the following commands will be executed:</info>\n<fg=cyan>> %s</fg>",
                implode(PHP_EOL . '> ', $commands)
            );
        }

        foreach ($commands as $command) {
            system($command, $var);
        }
        return '';
    }
}
