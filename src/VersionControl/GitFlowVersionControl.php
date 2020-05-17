<?php

namespace Hoogi91\ReleaseFlow\VersionControl;

use Version\Version;

/**
 * Class GitFlowVersionControl
 * @package Hoogi91\ReleaseFlow\VersionControl
 */
class GitFlowVersionControl extends GitVersionControl
{

    /**
     * Get the command line string that will be an argument of executeCommand and start the version control flow
     *
     * @param Version $version
     * @param string $branchType
     *
     * @return string[]
     */
    protected function getStartCommands(Version $version, string $branchType = self::RELEASE): array
    {
        if ($branchType === self::RELEASE) {
            return [sprintf('git flow release start %s', $version->getVersionString())];
        }

        if ($branchType === self::HOTFIX) {
            return [sprintf('git flow hotfix start %s', $version->getVersionString())];
        }
        return [];
    }

    /**
     * Get the command line string that will be an argument of executeCommand and finish the version control flow
     *
     * @param Version $version
     * @param string $branchType
     * @param boolean $publish
     *
     * @return string[]
     */
    protected function getFinishCommands(
        Version $version,
        string $branchType = self::RELEASE,
        bool $publish = false
    ): array {
        if ($branchType === self::RELEASE) {
            return [
                vsprintf(
                    'git flow release finish %1$s-m "Tagging version %2$s" %2$s',
                    [
                        ($publish ? '-p ' : ''),
                        $version->getVersionString(),
                    ]
                ),
            ];
        }

        if ($branchType === self::HOTFIX) {
            return [
                vsprintf(
                    'git flow hotfix finish %1$s-m "Tagging version %2$s" %2$s',
                    [
                        ($publish ? '-p ' : ''),
                        $version->getVersionString(),
                    ]
                ),
            ];
        }
        return [];
    }
}
