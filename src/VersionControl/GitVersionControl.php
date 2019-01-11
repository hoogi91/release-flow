<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Hoogi91\ReleaseFlow\Exception;
use PHPGit\Git as PHPGit;
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
     * @var PHPGit
     */
    protected $git;

    /**
     * GitVersionControl constructor.
     *
     * @param string $cwd
     */
    public function __construct(string $cwd)
    {
        $this->git = new PHPGit();
        $this->git->setRepository($cwd);
    }

    /**
     * @return string
     */
    public function getCurrentBranch()
    {
        //quieted: see https://github.com/kzykhys/PHPGit/issues/4
        @$branches = $this->git->branch();
        if (empty($branches)) {
            return '';
        }

        foreach ($branches as $branch) {
            if (isset($branch['current']) && $branch['current'] === true) {
                return $branch['name'];
            }
        }
        return '';
    }

    /**
     * @return bool
     */
    public function hasLocalModifications()
    {
        $indicators = array(
            \PHPGit\Command\StatusCommand::MODIFIED,
            \PHPGit\Command\StatusCommand::UNTRACKED,
        );

        $status = $this->git->status();
        foreach ($status['changes'] as $mod) {
            if (in_array($mod['index'], $indicators)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Version[]
     */
    public function getTags()
    {
        $tags = $this->git->tag();
        if (empty($tags)) {
            return [];
        }

        $versions = [];
        foreach ($tags as $versionNumber) {
            try {
                $versions[] = Version::fromString($versionNumber);
            } catch (InvalidVersionStringException $e) {
                // TODO: log invalid version string exceptions
            }
        }
        return $versions;
    }

    /**
     * @param string $commitMsg
     *
     * @return bool
     */
    public function saveWorkingCopy($commitMsg = '')
    {
        $this->git->add('.');
        return $this->git->commit($commitMsg);
    }

    /**
     * Start a git flow release.
     *
     * @param Version $version
     */
    public function startRelease(Version $version)
    {
        $this->executeGitCommand(sprintf('flow release start %s', $version->getVersionString()));
    }

    /**
     * Finishes the current git flow release without tagging.
     *
     * @param boolean $publish
     *
     * @throws Exception
     */
    public function finishRelease($publish = false)
    {
        if ($this->isRelease() === false) {
            throw new Exception('Can\'t finish release if current branch isn\'t the release branch');
        }

        $this->executeGitCommand(vsprintf('flow release finish %1$s-m "Tagging version %2$s" %2$s 1>/dev/null 2>&1', [
            ($publish ? '-p ' : ''),
            $this->getFlowVersion()->getVersionString(),
        ]));

        // TODO: in normal git version we need to create tag itself:
        // $this->git->tag->create($tagName)
    }

    /**
     * Start a git flow hotfix.
     *
     * @param Version $version
     */
    public function startHotfix(Version $version)
    {
        $this->executeGitCommand(sprintf('flow hotfix start %s', $version->getVersionString()));
    }

    /**
     * Finishes the current git flow hotfix without tagging.
     *
     * @param boolean $publish
     *
     * @throws Exception
     */
    public function finishHotfix($publish = false)
    {
        if ($this->isHotfix() === false) {
            throw new Exception('Can\'t finish hotfix if current branch isn\'t the hotfix branch');
        }

        $this->executeGitCommand(vsprintf('flow hotfix finish %1$s-m "Tagging version %2$s" %2$s 1>/dev/null 2>&1', [
            ($publish ? '-p ' : ''),
            $this->getFlowVersion()->getVersionString(),
        ]));

        // TODO: in normal git version we need to create tag itself:
        // $this->git->tag->create($tagName)
    }

    /**
     * Executes a git command.
     *
     * @param string $cmd
     *
     * @return mixed
     */
    private function executeGitCommand($cmd)
    {
        /**
         * @link http://stackoverflow.com/a/10986987
         * @link https://github.com/symfony/symfony/pull/3565
         * @link https://github.com/symfony/symfony/issues/3555
         * $builder = $this->git->getProcessBuilder();
         * $builder->inheritEnvironmentVariables(true); //
         * $builder->add($cmd);
         * $process = $builder->getProcess();
         *
         */

        if ($this->dryRun !== false) {
            return $cmd;
        }

        $var = null;
        system('git ' . $cmd, $var);
        return $var;
        //return $this->git->run($process);
    }
}
