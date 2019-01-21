<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Exception\VersionControlException;
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
    public function setWorkingDirectory(string $workingDirectory);

    /**
     * set dry-run indicator to prevent execution of version control commands
     *
     * @param boolean $flag
     */
    public function setDryRun(bool $flag);

    /**
     * check if current command can be processed by this version control system type
     *
     * @param string $command
     *
     * @return boolean
     */
    public function canProcessCommand(string $command);

    /**
     * Return array list of all branches
     *
     * @return array
     */
    public function getBranches();

    /**
     * Return the current branch
     *
     * @return string
     */
    public function getCurrentBranch();

    /**
     * Returns the hight valid version.
     *
     * @return Version
     */
    public function getCurrentVersion();

    /**
     * Returns the current flow version number => see getFlowBranch if branch is release or hotfix
     *
     * @return Version
     */
    public function getFlowVersion();

    /**
     * Return all tags of the project
     *
     * @return Version[]
     */
    public function getTags();

    /**
     * Returns if if local modifications exist.
     *
     * @return boolean
     */
    public function hasLocalModifications();

    /**
     * revert all working copy changes
     *
     * @return bool
     */
    public function revertWorkingCopy();

    /**
     * Save the local modifications (commit)
     *
     * @param $commitMsg
     *
     * @return boolean
     */
    public function saveWorkingCopy(string $commitMsg = '');

    /**
     * Return if current branch is release branch
     *
     * @return boolean
     */
    public function isRelease();

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
     * @return boolean
     */
    public function isHotfix();

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
