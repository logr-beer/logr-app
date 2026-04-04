---
name: release
description: Create a new versioned release — bumps version, generates changelog from commits, creates PR, and tags after merge.
---

# /release - Create a new versioned release

## Usage
`/release [patch|minor|major]` or `/release [version]`

Examples:
- `/release patch` - bump 0.1.6 to 0.1.7
- `/release minor` - bump 0.1.6 to 0.2.0
- `/release major` - bump 0.1.6 to 1.0.0
- `/release 0.2.0` - set explicit version
- `/release` - defaults to patch

## Steps

Follow these steps in order. Stop and ask the user if anything goes wrong.

### 1. Determine current version and next version

- Read the current version from `config/logr.php` (the `version` key)
- Parse the argument to determine the next version:
  - `patch` (default): increment the patch number (0.1.6 -> 0.1.7)
  - `minor`: increment minor, reset patch (0.1.6 -> 0.2.0)
  - `major`: increment major, reset minor and patch (0.1.6 -> 1.0.0)
  - If the argument looks like a version number (e.g. `0.2.0`), use it directly
- Confirm the version bump with the user before proceeding

### 2. Generate changelog from recent commits

- Run `git log` from the last tag to HEAD with `--oneline --no-merges` to get commit messages
- Group changes into Keep a Changelog categories based on commit messages:
  - **Added** - new features
  - **Changed** - changes to existing functionality
  - **Fixed** - bug fixes
  - **Removed** - removed features
- Use your judgment to categorize. Clean up commit messages into readable changelog entries.
- Omit version bump commits and merge commits.
- Show the generated changelog to the user and ask for approval or edits before continuing.

### 3. Update version files

- Update `config/logr.php` with the new version string
- Update `CHANGELOG.md`:
  - Add new version section after the header, before existing entries
  - Use today's date in YYYY-MM-DD format
  - Add the changelog link at the bottom with the other version links
  - Format: `[version]: https://github.com/logr-beer/logr-app/releases/tag/v{version}`

### 4. Commit and push branch

- Create a new branch: `release/v{version}`
- Stage only the changed files (`config/logr.php`, `CHANGELOG.md`)
- Commit with message: `Bump to v{version}`
- Push the branch to origin

### 5. Create PR

- Create a PR using `gh pr create` with:
  - Title: `Release v{version}`
  - Body: the changelog entry for this version
- Show the PR URL to the user
- Tell the user to merge the PR, then run `/release-tag` or ask you to tag it

### 6. Wait for merge, then tag

- Ask the user to confirm the PR has been merged
- Once confirmed:
  - `git checkout main && git pull origin main`
  - `git tag v{version}`
  - `git push origin v{version}`
- Report the tag URL to the user
