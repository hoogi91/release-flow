<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Exception;
use Version\Version;

/**
 * Interface for version control systems.
 */
interface VersionControlInterface
{
    /**
     * @param string $command
     *
     * @return boolean
     */
    public function canProcessCommand(string $command);

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
     * Save the local modifications (commit)
     *
     * @param $commitMsg
     *
     * @return boolean
     */
    public function saveWorkingCopy($commitMsg = '');

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
     * @throws Exception
     */
    public function finishRelease($publish = false);

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
     * @throws Exception
     */
    public function finishHotfix($publish = false);
}
