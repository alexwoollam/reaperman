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
- Global (adds `~/.composer/vendor/bin` to PATH):
  - `composer global require reaperman/reaperman`
- Per-project (dev):
  - `composer require --dev reaperman/reaperman`
  - Run via `./vendor/bin/reaperman`

## Quickstart
- Table output: `./bin/reaperman --path=./src`
- JSON output: `./bin/reaperman --path=. --format=json`
- Fail CI on findings: `./bin/reaperman --path=. --exit-nonzero-on-findings`
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

## Testing
- PHPUnit config: `phpunit.xml.dist`
- Run tests: `composer test`

## Contributing
- See `AGENTS.md` for repository structure, coding style (PSR-12), commit/PR conventions, and tooling.

## License
MIT
