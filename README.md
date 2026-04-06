# Fledge

**Laravel 13 application skeleton, optimized for PHP 8.5**

This is the app skeleton for [Fledge](https://github.com/webpatser/fledge-framework) — a drop-in replacement for Laravel's framework that uses PHP 8.5's native features for ~17% better performance.

## Quick Start

```bash
composer create-project webpatser/fledge my-app
cd my-app
php artisan serve
```

## What You Get

A standard Laravel 13 application that uses `webpatser/fledge-framework` instead of `laravel/framework`. Everything works the same — same Artisan commands, same directory structure, same ecosystem compatibility. Just faster.

Out of the box, Fledge includes:

- **Non-blocking Redis** via `amphp/redis` for cache, session, queue, and lock operations
- **Non-blocking HTTP** via `amphp/http-client` replacing cURL as the Guzzle transport
- **Non-blocking Database** via [`fledge-fiber-database`](https://github.com/webpatser/fledge-fiber-database) — Fiber-based MySQL, MariaDB, and PostgreSQL drivers with concurrent query support

### Fiber Database Drivers

Set `DB_CONNECTION=amphp-mysql` in `.env` to use the non-blocking MySQL driver. Queries suspend the current Fiber while waiting for I/O, allowing other Fibers to progress concurrently.

Run multiple queries in parallel:

```php
use Fledge\FiberDatabase\FiberDB;

[$users, $posts, $count] = FiberDB::concurrent(
    fn() => User::where('active', true)->get(),
    fn() => Post::latest()->limit(10)->get(),
    fn() => Comment::where('approved', false)->count(),
);
```

Available drivers: `amphp-mysql`, `amphp-mariadb`, `amphp-pgsql`. Connection config is preconfigured in `config/database.php`.

See [fledge-framework](https://github.com/webpatser/fledge-framework) for details on what's optimized and why.

## Switching an Existing Laravel 13 Project

You don't need this skeleton. Just swap the framework in your existing project:

```bash
# Add Fledge as a repository source
composer config repositories.fledge vcs https://github.com/webpatser/fledge-framework

# Replace Laravel's framework with Fledge
composer require "laravel/framework:^13.3" -W
```

To switch back:

```bash
composer config --unset repositories.fledge
composer require "laravel/framework:^13.0" -W
```

## Requirements

- PHP 8.5+
- intl extension
- Composer 2.x

## Credits

Built on [Laravel](https://laravel.com) by [Taylor Otwell](https://github.com/taylorotwell) and the Laravel community.

## License

MIT
