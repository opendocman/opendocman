# How to contribute

Third-party patches are essential for keeping OpenDocMan improving. We cannot
access the huge number of platforms for running OpenDocMan. We also want to keep it as 
easy as possible to contribute changes that get things working in your environment. 
There are a few guidelines that we need contributors to follow so that we can 
have a chance of keeping ontop of things.

## Getting Started

* Make sure you have a [GitHub account](https://github.com/signup/free)
* Submit a github ticket for your issue, assuming one does not already exist.
  * Clearly describe the issue including steps to reproduce when it is a bug.
  * Make sure you fill in the earliest version that you know has the issue.
* Fork the repository on GitHub

## Making Changes

* Create a topic branch from where you want to base your work.
  * This is usually the develop branch (aka future features / fixes).
  * To quickly create a topic branch based on develop; `git checkout -b
    feature/issuexxx-my_contribution master`. Please avoid working directly on the
    `master` branch.
* Make commits of logical units.
* Check for unnecessary whitespace with `git diff --check` before committing.
* Make sure your commit messages are in the proper format.

### Conventional Commits

This repository uses [Release Please](https://github.com/googleapis/release-please-action)
to auto-generate releases from commits pushed to `master`. It parses
[Conventional Commits](https://www.conventionalcommits.org/) to determine the
next version number and generate changelog entries.

Format:

```
type(scope): description

body
```

The `type` determines the version bump:

| Type | Bump | Example |
|------|------|---------|
| `fix` | patch (2.0.2 → 2.0.3) | `fix: redirect deleted users to login page` |
| `feat` | minor (2.0.2 → 2.1.0) | `feat: add batch upload` |
| `feat!` or body with `BREAKING CHANGE:` | major (2.0.2 → 3.0.0) | `feat!: drop PHP 7 support` |
| `chore`, `docs`, `refactor`, `test`, `style`, `build`, `ci`, `perf` | none | `test: fix deprecation warnings` |

Link issues in the body with `fixes #123` or `closes #456`.

Examples:

```
fix: redirect deleted users to login page

If a user is deleted while logged in, their session uid still exists
but the database record is gone. Validate the session user against the
database on each request and redirect to login if not found.

Fixes #14
```

```
test: fix deprecation warnings and skipped tests

Replace deprecated (double) cast with (float), remove no-op
setAccessible(true) calls, and rewrite skipped SettingsTest methods
to not depend on the ABSPATH constant.

Closes #365
```

* Make sure you have added the necessary tests for your changes.
* Run _all_ the tests to assure nothing else was accidentally broken.

## Making Trivial Changes

### Documentation

For changes of a trivial nature to comments and documentation, it is not
always necessary to create a new ticket in Github. In this case, it is
appropriate to start the first line of a commit with '(doc)' instead of
an issue number. 

````
    (doc) Add documentation commit example to CONTRIBUTING

    There is no example for contributing a documentation commit
    to the OpenDocMan repository. This is a problem because the contributor
    is left to assume how a commit of this nature may appear.

    The first line is a real life imperative statement with '(doc)' in
    place of what would have been the ticket number in a 
    non-documentation related commit. The body describes the nature of
    the new documentation or comments added.
````

## Submitting Changes

* By submitting code changes to the OpenDocMan project you agree to our 
  [Contributors Agreement] (http://www.opendocman.com/contributors-license-agreement/)
* Push your changes to a topic branch in your fork of the repository.
* Submit a pull request to the repository in the opendocman organization.
* After feedback has been given we expect responses within two weeks. After two
  weeks will may close the pull request if it isn't showing any activity.

# Additional Resources

* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)
* [OpenDocMan Contributors License Agreement] (http://www.opendocman.com/contributors-license-agreement/)
