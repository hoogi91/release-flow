<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Command\FinishCommand;
use Hoogi91\ReleaseFlow\Command\HotfixCommand;
use Hoogi91\ReleaseFlow\Command\StartCommand;
use Hoogi91\ReleaseFlow\Exception;
use Version\Exception\InvalidVersionStringException;
use Version\Version;

/**
 * Class AbstractVersionControl
 * @package Hoogi91\ReleaseFlow\VersionControl
 */
abstract class AbstractVersionControl implements VersionControlInterface
{
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
     * dry-run: do nothing
     * @var boolean
     */
    protected $dryRun = false;

    /**
     * @var array
     */
    protected $dryRunCommandWords = array('tag', 'push', 'add', 'commit');

    /**
     * @param boolean $flag
     */
    public function setDryRun($flag)
    {
        $this->dryRun = (bool)$flag;
    }

    /**
     * @param string $command
     *
     * @return bool
     * @throws Exception
     */
    public function canProcessCommand(string $command)
    {
        $canProcess = in_array($command, static::ALLOWED_COMMANDS);
        if ($canProcess !== true) {
            return false;
        }

        if ($command === FinishCommand::class && !$this->isInTheFlow()) {
            throw new Exception(sprintf(
                'You are not in a flow release branch (%s).',
                $this->getCurrentBranch()
            ));
        } elseif ($command !== FinishCommand::class && $this->isInTheFlow()) {
            throw new Exception('You are already in a flow branch.');
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
     * Returns the current flow version number => see getFlowBranch if branch is release or hotfix
     *
     * @return Version
     * @throws Exception
     */
    public function getFlowVersion()
    {
        if (!$this->isInTheFlow()) {
            throw new Exception('Flow version can\'t be determined cause no flow is started!');
        }

        // get current branch name
        $branch = $this->getCurrentBranch();

        try {
            // try to get release number from hotfix or release
            if ($this->isHotfix()) {
                return Version::fromString(str_replace(static::HOTFIX . '/', '', $branch));
            }
            return Version::fromString(str_replace(static::RELEASE . '/', '', $branch));
        } catch (InvalidVersionStringException $e) {
            throw new Exception('Cannot detect version in branch name: ' . $branch);
        }
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
}