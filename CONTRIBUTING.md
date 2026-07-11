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
* Make sure your commit messages use [Conventional Commits](https://www.conventionalcommits.org/):

  ````
      feat: add unified installer & migration system

      Overhauls the installation system with versioned migrations,
      config wizard, and CLI support.

  ````

  | Prefix         | Effect on version |
  |----------------|-------------------|
  | `feat:`        | Minor bump (1.0.0 → 1.1.0) |
  | `fix:`         | Patch bump (1.0.0 → 1.0.1) |
  | `BREAKING CHANGE:` or `feat!:` | Major bump (1.0.0 → 2.0.0) |
  | `docs:`, `chore:`, `ci:`, `refactor:` | No bump |

  Commits without a recognized prefix are still accepted but won't
  trigger a version bump or appear in the changelog.

* Run _all_ the tests before pushing (automatic via pre-push hook):

      make test

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

## Release Process

OpenDocMan uses [Release Please](https://github.com/googleapis/release-please-action)
for automated semantic versioning. No manual version bumps needed.

1. Merge feature/fix PRs into `master` using conventional commit messages.
2. Release Please maintains a "Release PR" that accumulates changes.
3. When ready to ship, merge the Release PR — it will:
   - Bump the version in `application/version.php`
   - Update `CHANGELOG.md`
   - Create a git tag (e.g. `v2.1.0`)
   - Publish a GitHub Release with auto-generated notes

# Additional Resources

* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)
* [OpenDocMan Contributors License Agreement] (http://www.opendocman.com/contributors-license-agreement/)
