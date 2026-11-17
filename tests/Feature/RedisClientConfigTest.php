<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedisClientConfigTest extends TestCase
{
    public function test_default_redis_client_has_a_connector_when_redis_client_is_unset(): void
    {
        $saved = [getenv('REDIS_CLIENT'), $_ENV['REDIS_CLIENT'] ?? null, $_SERVER['REDIS_CLIENT'] ?? null];

        putenv('REDIS_CLIENT');
        unset($_ENV['REDIS_CLIENT'], $_SERVER['REDIS_CLIENT']);

        try {
            $config = require config_path('database.php');
        } finally {
            if ($saved[0] !== false) {
                putenv('REDIS_CLIENT='.$saved[0]);
            }
            if ($saved[1] !== null) {
                $_ENV['REDIS_CLIENT'] = $saved[1];
            }
            if ($saved[2] !== null) {
                $_SERVER['REDIS_CLIENT'] = $saved[2];
            }
        }

        $client = $config['redis']['client'];

        $this->assertSame('fledge', $client);
        $this->assertTrue($this->app->bound("redis.connector.{$client}"), "No connector registered for Redis client [{$client}].");
    }
}
