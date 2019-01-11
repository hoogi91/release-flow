# Release with the flow.
This project is going to be a successor of release-manager. It will be a zero-conf tool 
to version PHP project releases semantically, tightly coupled with composer and git-flow.

![screenshot](doc/screen.png "Usage")


## Usage
```bash
release-flow start
release-flow finish
```
or to hotfix (patch-bump version based on master branch)
```bash
release-flow hotfix
release-flow finish
```

## ToDo's
- replace `kzykhys/git` with `teqneers/php-stream-wrapper-for-git` or `cpliakas/git-wrapper`