# Changelog

All notable changes are documented here ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/)).

## [Unreleased]

### Added
- Phase 0 foundation: project rules (`CLAUDE.md`), repo agent skills (`.claude/skills/`),
  staged documentation (`docs/`), interview round 1, AI image prompt workflow (`ai/`).
- GitHub Actions: CI (lint, coding standard, PHPUnit on MySQL 8.0), commit policy, manual release deploy over Cloudflare Access SSH.
- `.githooks/commit-msg` enforcing Arumi Studios authorship and no AI attribution.
- `Modules\` namespace for feature modules (`app/Modules`).

### Changed
- Switched from the CodeIgniter framework distribution to the appstarter layout; framework 4.7 installed via Composer.
