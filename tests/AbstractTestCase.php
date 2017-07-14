<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\UserProvider;
use Tests\NullGate;
use Tests\NullUserProvider;

abstract class AbstractTestCase extends BaseTestCase
{

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        // Your code here
    }

    /**
     * Define environment setup.
     *
     * - Sqlite in memory database
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app->bind(Gate::class, NullGate::class);
        $app->bind(UserProvider::class, NullUserProvider::class);
    }
    
    /**
     * Loads the service provider during the tests
     */
    protected function getPackageProviders($app)
    {
        return ['Avvertix\TusUpload\Providers\TusUploadServiceProvider'];
    }

}
