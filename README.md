# Release with the flow.
> SemVer-compliant release versioning/version bumping with git-flow incl. providers to update additional project files.

![screenshot](doc/screen.png "Usage")

## Features
* SemVer-compliant tagging of Git repository
* SemVer-compliant tagging of additional files via file providers (includes file provider for composer.json)
* command execution with [gitflow](https://github.com/nvie/gitflow) or plain Git installation
* interactive and non-interactive command line usage

## Installation

**Installation using Composer**

The recommended way to install this tool is by using [Composer](https://getcomposer.org/). In your Composer project root, just do `composer req hoogi91/release-flow --dev`

**Installation using Download**

If you want to use `release-flow.phar` you can simply download latest version  [here](https://github.com/hoogi91/release-flow/releases). The usage is the same as below but just replace `bin/release-flow` with `release-flow.phar`

## Configuration
If you are working on a composer-based project you can configure which VCS and file provider classes are used when executing release-flow commands. Just put a similiar config like below to your composer.json:
```json
{
  "extra": {
    "hoogi91/release-flow": {
      "vcs": "Hoogi91\\ReleaseFlow\\VersionControl\\GitFlowVersionControl",
      "provider": [
        "Vendor\\MyReleaseFlowExtension\\FileProvder\\MyFileProvider"
      ]
    }
  }
}
```

**Option "vcs" (string)**

Version Control class that must implement interface `Hoogi91\ReleaseFlow\VersionControl\VersionControlInterface` and executes commands on system level.

**Option "provider" (array)**

File Provider classes that must implement interface `Hoogi91\ReleaseFlow\FileProvider\FileProviderInterface` to set/update new version string in additional project files.

## Usage
get explanation to application and its commands
```bash
> bin/release-flow help
```

### Start Command
```bash
# start interactive console to bump major, minor or patch version
> bin/release-flow start

# start interactive console and do not execute any command
> bin/release-flow start --dry-run

# start new minor release flow without interactive console
> bin/release-flow start --increment=minor --force

# get more info and help
> bin/release-flow help start
```

or to start a hotfix branch (patch-bump version based on master branch)
```bash
> bin/release-flow hotfix
```

### Finish Command
```bash
# start interactive console to finish current release or hotfix branch
> bin/release-flow finish

# start interactive console and do not execute any command
> bin/release-flow finish --dry-run

# get more info and help
> bin/release-flow help finish
```

### DEV Version Bump Command (optional usage)
> DEV Version bump will not affect VCS Tags!

If you prefer a "dev" flag in your version string until next version/release is published you can simply execute the following command to execute all file providers (see above feature list) and commit the changes to VCS: 
```bash
# start interactive session to e.g. update current version "2.1.5" to "2.1.6-dev" 
> bin/release-flow dev

# force update and commit of current version "2.1.5" to "2.1.6-dev" 
> bin/release-flow dev --force
```

### Status Command
To get short version/branch information of current selected repository:
```bash
> bin/release-flow status
```