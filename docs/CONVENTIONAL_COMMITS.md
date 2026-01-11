# Conventional Commits Guide for Roble

This guide explains the commit standard we use in the Roble project and how it relates to our automatic versioning system.

## What are Conventional Commits?

Conventional Commits is a standard for writing commit messages that facilitates automatic changelog generation and semantic versioning. Each commit follows a specific format that describes the type of change made.

Official reference: https://www.conventionalcommits.org/

## Standard Format

```
<type>: <brief description>

[optional body]

[optional footer]
```

### Real Example

```bash
feat: implement payroll calculation

Added functionality to calculate regular and overtime
payroll with all applicable concepts.
```

## Commit Types

### Types that **DO** increment version:

| Type                           | Increment | Description                   | Example                                     |
| ------------------------------ | --------- | ----------------------------- | ------------------------------------------- |
| `fix:`                         | PATCH     | Bug fixes                     | `fix: correct overtime payroll calculation` |
| `feat:`                        | MINOR     | New functionality             | `feat: implement PDF reports module`        |
| `feat!:` or `BREAKING CHANGE:` | MAJOR     | Breaking compatibility change | `feat!: change database structure`          |

### Types that **DO NOT** increment version:

| Type        | Description        | Example                             |
| ----------- | ------------------ | ----------------------------------- |
| `docs:`     | Documentation only | `docs: update README`               |
| `style:`    | Code formatting    | `style: apply Pint`                 |
| `refactor:` | Refactoring        | `refactor: simplify authentication` |
| `test:`     | Tests              | `test: add tax tests`               |
| `chore:`    | Maintenance        | `chore: update Laravel`             |
| `perf:`     | Performance        | `perf: optimize SQL queries`        |

## Important Rules

1. **All lowercase**: `feat:` not `Feat:`
2. **No final period**: `fix: correct error` not `fix: correct error.`
3. **Present tense verb**: "implement", "correct", "add"
4. **Concise**: Maximum 72 characters in the first line

## Breaking Changes

```bash
# Option 1: ! symbol
feat!: change DB structure

# Option 2: Footer
feat: change DB structure

BREAKING CHANGE: The DB schema has changed.
```

## Versioning Workflow

- **Only docs/refactor/style**: No version is created
- **At least one fix**: PATCH (1.0.0 → 1.0.1)
- **At least one feat**: MINOR (1.0.0 → 1.1.0)
- **Breaking change**: MAJOR (1.0.0 → 2.0.0)

Version includes hash: `v1.0.1+a3f2c1b`

## Local Testing

```bash
# See next version without making changes
npm run version:check

# Direct script
bash scripts/auto-version.sh --dry-run
```

## Examples

✅ **Yes:**

```bash
git commit -m "fix: correct login validation"
git commit -m "feat: implement filters in cases table"
```

❌ **No:**

```bash
git commit -m "changes"
git commit -m "WIP"
```

## References

- [Conventional Commits](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)
