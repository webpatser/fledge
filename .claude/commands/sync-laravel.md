# Sync Laravel Upstream

Fetch the latest Laravel framework changes and merge them into Fledge.

## Steps

1. **Fetch upstream changes**:
   ```bash
   cd packages/framework
   git fetch upstream
   ```

2. **Create sync branch**:
   ```bash
   git checkout fledge-main
   git checkout -b sync/$(date +%Y%m%d)
   ```

3. **Merge upstream**:
   ```bash
   git merge upstream/12.x --no-commit
   ```

4. **Resolve conflicts** - Prefer Fledge optimizations for:
   - `src/Illuminate/Support/Arr.php` (array_first/last)
   - `src/Illuminate/Support/Collection.php` (pipe operator)
   - `src/Illuminate/Http/Request.php` (clone-with)

5. **Run tests**:
   ```bash
   vendor/bin/pest
   ```

6. **Commit and merge**:
   ```bash
   git commit -m "Sync Laravel upstream $(date +%Y-%m-%d)"
   git checkout fledge-main
   git merge sync/$(date +%Y%m%d)
   ```

## After Sync

- Review any new Laravel features for PHP 8.5 optimization opportunities
- Update CLAUDE.md if new files were added
- Run full test suite from project root
