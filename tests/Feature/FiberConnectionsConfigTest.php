<?php

namespace Tests\Feature;

use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FiberConnectionsConfigTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function fiberConnections(): array
    {
        return [
            'mysql' => ['fledge-mysql'],
            'mariadb' => ['fledge-mariadb'],
            'pgsql' => ['fledge-pgsql'],
        ];
    }

    #[DataProvider('fiberConnections')]
    public function test_shipped_fiber_connection_uses_a_registered_driver(string $name): void
    {
        $config = config("database.connections.{$name}");

        $this->assertIsArray($config, "Connection [{$name}] is not configured.");
        $this->assertSame($name, $config['driver']);
        $this->assertArrayNotHasKey('url', $config);
        $this->assertTrue($this->app->bound("db.connector.{$name}"), "No connector registered for [{$name}].");
    }

    #[DataProvider('fiberConnections')]
    public function test_fiber_connection_keeps_its_driver_when_db_url_is_set(string $name): void
    {
        $saved = [getenv('DB_URL'), $_ENV['DB_URL'] ?? null, $_SERVER['DB_URL'] ?? null];
        $url = 'mysql://user:secret@db.example.test:3307/app';

        putenv('DB_URL='.$url);
        $_ENV['DB_URL'] = $url;
        $_SERVER['DB_URL'] = $url;

        try {
            $config = require config_path('database.php');
        } finally {
            $saved[0] === false ? putenv('DB_URL') : putenv('DB_URL='.$saved[0]);
            if ($saved[1] === null) {
                unset($_ENV['DB_URL']);
            } else {
                $_ENV['DB_URL'] = $saved[1];
            }
            if ($saved[2] === null) {
                unset($_SERVER['DB_URL']);
            } else {
                $_SERVER['DB_URL'] = $saved[2];
            }
        }

        $parsed = (new ConfigurationUrlParser)->parseConfiguration($config['connections'][$name]);

        $this->assertSame($name, $parsed['driver']);

        config(["database.connections.{$name}" => $config['connections'][$name]]);
        DB::purge($name);

        $this->assertSame($name, DB::connection($name)->getDriverName());

        DB::purge($name);
    }

    #[DataProvider('fiberConnections')]
    public function test_shipped_fiber_connection_resolves_without_connecting(string $name): void
    {
        $connection = DB::connection($name);

        $this->assertSame($name, $connection->getDriverName());

        DB::purge($name);
    }
}
