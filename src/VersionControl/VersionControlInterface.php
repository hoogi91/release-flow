<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Exception\VersionControlException;
use Symfony\Component\Console\Output\OutputInterface;
use Version\Version;

/**
 * Interface VersionControlInterface
 * @package Hoogi91\ReleaseFlow\VersionControl
 */
interface VersionControlInterface
{

    /**
     * set working directory of repository where commands should be executed
     *
     * @param string $workingDirectory
     */
    public function setWorkingDirectory(string $workingDirectory): void;

    /**
     * set dry-run indicator to prevent execution of version control commands
     *
     * @param OutputInterface $output
     */
    public function setDryRun(OutputInterface $output): void;

    /**
     * check if current command can be processed by this version control system type
     *
     * @param string $command
     *
     * @return bool
     */
    public function canProcessCommand(string $command): bool;

    /**
     * Return array list of all branches
     *
     * @return array
     */
    public function getBranches(): array;

    /**
     * Return the current branch
     *
     * @return string
     */
    public function getCurrentBranch(): string;

    /**
     * Returns the current valid version.
     *
     * @return Version
     */
    public function getCurrentVersion(): Version;

    /**
     * Returns the current flow version number => see getFlowBranch if branch is release or hotfix
     *
     * @return Version
     */
    public function getFlowVersion(): Version;

    /**
     * Return all tags of the project
     *
     * @return Version[]
     */
    public function getTags(): array;

    /**
     * Returns if if local modifications exist.
     *
     * @return bool
     */
    public function hasLocalModifications(): bool;

    /**
     * revert all working copy changes
     *
     * @return bool
     */
    public function revertWorkingCopy(): bool;

    /**
     * Save the local modifications (commit)
     *
     * @param string $commitMsg
     *
     * @return bool
     */
    public function saveWorkingCopy(string $commitMsg = ''): bool;

    /**
     * Return if current branch is release branch
     *
     * @return bool
     */
    public function isRelease(): bool;

    /**
     * Start a git flow release.
     *
     * @param Version $version
     */
    public function startRelease(Version $version);

    /**
     * Finishes the current git flow release without tagging.
     *
     * @param boolean $publish whether to publish to $ORIGIN after finishing
     *
     * @throws VersionControlException
     */
    public function finishRelease(bool $publish = false);

    /**
     * Return if current branch is hotfix branch
     *
     * @return bool
     */
    public function isHotfix(): bool;

    /**
     * Start a git flow hotfix.
     *
     * @param Version $version
     */
    public function startHotfix(Version $version);

    /**
     * Finishes the current git flow hotfix without tagging.
     *
     * @param boolean $publish whether to publish to $ORIGIN after finishing
     *
     * @throws VersionControlException
     */
    public function finishHotfix(bool $publish = false);
}
