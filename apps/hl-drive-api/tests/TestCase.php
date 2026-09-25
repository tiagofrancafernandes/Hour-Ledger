<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * Override database configuration to use PostgreSQL for tests.
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => '1010',
            'database' => 'hour_ledger_test',
            'username' => 'postgres',
            'password' => 'postgres',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->bound(\App\Services\TenantResolver::class)) {
            $resolver = $this->app->make(\App\Services\TenantResolver::class);
            $resolver->clear();
            $resolver::clearCache();
        }
    }

    protected function tearDown(): void
    {
        if ($this->app && $this->app->bound(\App\Services\TenantResolver::class)) {
            $resolver = $this->app->make(\App\Services\TenantResolver::class);
            $resolver->clear();
            $resolver::clearCache();
        }

        parent::tearDown();
    }
}
