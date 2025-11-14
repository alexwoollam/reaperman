<img src="docs/reaperman.svg" alt="Reaperman" width="250" />

# Reaperman

Reaperman is a Composer-installed CLI that scans PHP projects to find likely dead code (unused private methods and global functions). It uses nikic/php-parser to build a lightweight call map and reports findings in table or JSON formats.

## Features
- Detects unused private methods and global functions
- Fast filesystem scan with directory ignores
- Table and JSON output, non-zero exit for CI

## Requirements
- PHP 8.1+
- Composer

## Installation
- Global (recommended):
  - `composer global require reaperman/reaperman`
  - Ensure Composer's global bin is on PATH:
    - Linux: add `~/.config/composer/vendor/bin` to PATH
    - macOS: add `~/.composer/vendor/bin` to PATH
- Per-project (dev):
  - `composer require --dev reaperman/reaperman`
  - Run via `./vendor/bin/reaperman`

## Quickstart
- Global, from a project root: `reaperman` (scans current directory by default)
- Global, explicit path: `reaperman --path=. --format=table`
- Per-project: `./vendor/bin/reaperman --path=. -v`
- JSON output: add `--format=json`
- Fail CI on findings: add `--exit-nonzero-on-findings`
- Show scanned files: add `-v`

## Options
- `--path=DIR` Root directory to scan (default: current directory)
- `--ignore=LIST` Comma-separated directory basenames to skip (default: `vendor,node_modules,storage,cache`)
- `--format=table|json` Output format (default: `table`)
- `--exit-nonzero-on-findings` Exit code 1 if any findings

## Limitations
- Heuristic analysis: dynamic calls, reflection, DI containers, or string-based invocations may not be tracked and can cause false positives/negatives.
- Magic methods (`__construct`, `__invoke`, etc.) are never flagged.

## Development
- Install deps: `composer install`
- Run checks: `composer check` (lint + phpstan + tests)
- Run CLI from source: `./bin/reaperman --path=./src -v`

## Local Development (Path Repo)
- Per-project:
  - In your project `composer.json` add:
    - `"repositories": [{"type": "path", "url": "/var/www/reaperman", "options": {"symlink": true}}]`
    - `"require-dev": {"reaperman/reaperman": "dev-main"}` and ensure `"minimum-stability": "dev", "prefer-stable": true`.
  - Run: `composer update reaperman/reaperman -W`
  - Use: `./vendor/bin/reaperman --path=. --format=table`
- Global:
  - `composer global config repositories.reaperman path /var/www/reaperman`
  - `composer global config repositories.reaperman options.symlink true`
  - `composer global require reaperman/reaperman:dev-main`
  - Use: `reaperman --path=/your/project`
- Direct run (no Composer): `./bin/reaperman --path=/your/project`
- Tip: If Composer needs a version for local testing, either init Git or set `"version": "0.1.x-dev"` temporarily.

## Testing
- PHPUnit config: `phpunit.xml.dist`
- Run tests: `composer test`

## Contributing
- See `AGENTS.md` for repository structure, coding style (PSR-12), commit/PR conventions, and tooling.

## License
MIT
