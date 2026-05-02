# Fledge

**Laravel 13 application skeleton, optimized for PHP 8.5**

This is the app skeleton for [Fledge](https://github.com/webpatser/fledge-framework), a drop-in replacement for Laravel's framework that uses PHP 8.5's native features for ~17% better performance.

> Forked from [`laravel/laravel`](https://github.com/laravel/laravel). The pre-fork commit history is preserved on the [`legacy/laravel-history`](../../tree/legacy/laravel-history) branch.

## Quick Start

The skeleton is distributed via Git for now (not yet on Packagist), so clone it directly:

```bash
git clone https://github.com/webpatser/fledge my-app
cd my-app
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

After install, verify you are running Fledge and not stock Laravel:

```bash
bash vendor/webpatser/fledge-framework/bin/verify-fledge-install.sh
# OK   running Fledge framework v13.7.0.4
```

## What You Get

A standard Laravel 13 application that uses `webpatser/fledge-framework` instead of `laravel/framework`. Everything works the same: same Artisan commands, same directory structure, same ecosystem compatibility. Just faster.

Out of the box, Fledge includes:

- **Non-blocking I/O** via [`fledge-fiber`](https://github.com/webpatser/fledge-fiber): one Fiber-based async runtime covering Redis (cache, session, queue, locks), MySQL/MariaDB/PostgreSQL drivers, HTTP client and server, DNS resolution, and concurrency primitives. Previously split across `fledge-fiber-database`, `-redis`, and `-http`, now consolidated into a single package.
- **Concurrent Middleware**: run independent middleware in parallel using fibers via `ConcurrentMiddlewareGroup`.
- **Fiber Queue Worker** via [`torque`](https://github.com/webpatser/torque): concurrent job processing, a Horizon replacement.

### Ecosystem

```
fledge (this skeleton)
  └─ requires webpatser/fledge-framework   Laravel 13 fork, PHP 8.5 optimized
      └─ requires webpatser/fledge-fiber   Fiber async: Redis, DB, HTTP, DNS, primitives

Optional companions:
  webpatser/torque                         Fiber queue worker (Horizon alternative)
  webpatser/laravel-fiber                  Same FiberDriver, standalone for Laravel 11/12/13
```

### Caveats

Things worth knowing before going to production with Fledge:

- **Redis Cluster** is supported by `fledge-fiber` via Laravel's standard `clusters.*` config. Multi-key commands must share a hash tag (`{tag}.key`), `SELECT` to a non-zero database is rejected, MULTI/EXEC must stay within one slot, and pipelines route per-command (no atomicity across slots). For workloads that need cross-slot transactions, set `REDIS_CLIENT=phpredis`.
- **PHP 8.5 hosting** was released in November 2025. Availability across managed hosts (Forge, Vapor, Ploi, Laravel Cloud) is still rolling out, check your host before committing.
- **Active branch** is `fledge-13`, not `main`. Released tags follow `v13.X.Y.N` where `N` is the Fledge patch counter on top of Laravel `X.Y.0`.

### Fiber Database Drivers

Set `DB_CONNECTION=fledge-mysql` in `.env` to use the non-blocking MySQL driver. Queries suspend the current Fiber while waiting for I/O, allowing other Fibers to progress concurrently.

Run multiple queries in parallel:

```php
use Fledge\Fiber\Database\FiberDB;

[$users, $posts, $count] = FiberDB::concurrent(
    fn() => User::where('active', true)->get(),
    fn() => Post::latest()->limit(10)->get(),
    fn() => Comment::where('approved', false)->count(),
);
```

Available drivers: `fledge-mysql`, `fledge-mariadb`, `fledge-pgsql`. Connection config is preconfigured in `config/database.php`.

### Concurrent Middleware

Run independent middleware in parallel using fibers. Middleware that implement `ConcurrentMiddleware` can be grouped and executed concurrently, ideal for middleware that perform independent I/O like Redis lookups, API key validation, or rate limit checks.

```php
// In your Kernel
protected $concurrentMiddleware = [
    'io-checks' => [
        ValidateApiKey::class,      // Redis lookup
        CheckRateLimit::class,      // Redis lookup
        LoadSubscriptionTier::class // Redis lookup
    ],
];

protected $middlewareGroups = [
    'api' => [
        'concurrent:io-checks',    // 3 Redis calls in ~5ms instead of ~15ms
        SubstituteBindings::class,
    ],
];
```

Each middleware implements `before(Request): Request|Response` and `after(Request, Response): Response`. The group runs all `before()` methods concurrently via the FiberDriver, merges request modifications, continues down the pipeline, then runs all `after()` methods concurrently.

See [fledge-framework](https://github.com/webpatser/fledge-framework) for details on what's optimized and why.

## Switching an Existing Laravel 13 Project

You don't need this skeleton. Swap the framework in your existing project by requiring `webpatser/fledge-framework` directly:

```bash
composer require "webpatser/fledge-framework:^13.7" -W
```

This installs Fledge and removes `vendor/laravel/framework` via the `replace` mechanism. **Don't run `composer require "laravel/framework:..."`**, that pulls in upstream Laravel even if Fledge is registered as a repository. See the [framework README](https://github.com/webpatser/fledge-framework#why-composer-require-laravelframework-does-not-pull-in-fledge) for the resolver detail.

Verify the switch:

```bash
bash vendor/webpatser/fledge-framework/bin/verify-fledge-install.sh
```

To switch back to upstream Laravel:

```bash
composer remove webpatser/fledge-framework
composer require "laravel/framework:^13.0" -W
```

## Requirements

- PHP 8.5+
- intl extension
- Composer 2.x
- PHPUnit 11, 12, or 13 (matches laravel/framework support)

## Credits

Built on [Laravel](https://laravel.com) by [Taylor Otwell](https://github.com/taylorotwell) and the Laravel community.

## License

MIT
