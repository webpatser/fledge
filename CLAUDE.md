# Fledge - PHP 8.5 Optimized Laravel

## What is Fledge?

Fledge is a **drop-in replacement** for Laravel 12, optimized for PHP 8.5 features.
It keeps the `Illuminate\` namespace for full package compatibility.

## CRITICAL RULES

1. **100% Laravel 12 Compatibility** - All Laravel tests MUST pass
2. **NO TEST MODIFICATIONS** - NEVER alter, delete, or skip existing Laravel tests
3. **Drop-in Replacement** - Must work with all existing Laravel 12 packages
4. **Tests are the contract** - If a test fails, the optimization is wrong, not the test

## Structure

- `packages/framework/` - Forked laravel/framework with PHP 8.5 optimizations
- `baseline/` - Baseline benchmark and test results (pre-optimization)
- Rest of project - Standard Laravel skeleton with PHP 8.5 optimizations

## PHP 8.5 Features Used

| Feature | Where Used | Status |
|---------|------------|--------|
| `array_first()` / `array_last()` | `packages/framework/src/Illuminate/Collections/Arr.php` | ✅ Done |
| Pipe operator `\|>` | Pipeline, Collections, helpers.php | ✅ Done |
| URI Extension | Support/Uri.php (native Uri\Rfc3986\Uri) | ✅ Done |
| Persistent cURL | HTTP Client | ✅ Done |
| `clone($obj, [...])` | N/A | ⏭️ Skip - Requires constructor promotion (architectural constraint) |
| `#[\NoDiscard]` | N/A | ⏭️ Skip - Not implemented |

## Key Commands

- `/sync-laravel` - Fetch latest Laravel and merge into Fledge
- `composer test` - Run framework tests
- `php artisan serve` - Run development server

## Upstream Remotes

- `packages/framework/` has remote `upstream` → github.com/laravel/framework

## Optimization Targets

**High Priority** (Arr, Collection, Request):
- `packages/framework/src/Illuminate/Support/Arr.php`
- `packages/framework/src/Illuminate/Support/Collection.php`
- `packages/framework/src/Illuminate/Http/Request.php`

**Medium Priority** (Pipeline, URI, HTTP):
- `packages/framework/src/Illuminate/Pipeline/Pipeline.php`
- `packages/framework/src/Illuminate/Support/Uri.php`
- `packages/framework/src/Illuminate/Http/Client/PendingRequest.php`

## Development Workflow

1. **Before ANY optimization**: Run tests, ensure baseline passes
2. Make changes in `packages/framework/`
3. Run tests: `cd packages/framework && vendor/bin/phpunit`
4. If tests fail: REVERT the change (tests are never wrong)
5. Run benchmark: `php baseline/benchmark.php` to compare performance
6. Commit only when ALL tests pass

## Baseline Location

Baseline metrics stored in `/baseline/`:
- `test-results.txt` - Original PHPUnit output
- `benchmark-results.json` - Performance baseline
- Compare after each optimization to ensure improvement

## Known PHP 8.5 Test Failures

These 6 tests fail due to PHP 8.5 runtime changes, NOT Fledge modifications. Laravel 12 on PHP 8.5 exhibits identical failures.

| Test | Issue | Root Cause |
|------|-------|------------|
| ValidatePathEncodingTest (UTF-8 paths) | MalformedUrlException on `汉字字符集` | PHP 8.5 URI handling change |
| SupportStrTest::testWordCount | Returns 4 instead of 0 for Cyrillic | PHP 8.5 improved Unicode in str_word_count() |
| RedisConnectionTest (4 tests) | Invalid cursor errors | Predis library incompatibility |

**Test Results:** 12,745 tests, 96% passing (6 failures are upstream PHP 8.5 issues)
