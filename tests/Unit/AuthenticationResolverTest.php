<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Avvertix\TusUpload\TusUpload;

use Avvertix\TusUpload\Contracts\AuthenticationResolver as AuthenticationResolverContract;
use Avvertix\TusUpload\Auth\AuthenticationResolver;
use Mockery;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\UserProvider;
// use Illuminate\Support\Facades\Auth;

class AuthenticationResolverTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

    }

    /** @test */
    public function authentication_resolver_can_be_instantiated()
    {
        $resolver = $this->app->make(AuthenticationResolverContract::class);

        $this->assertNotNull($resolver);
    }

    /** @test */
    public function authentication_resolver_validates_user()
    {
        $credentials = ['api_token' => 'A1'];
        $tus_payload_metadata = new \stdClass;
        $expected_user = 'foo';
        
        //mock Gate
        $gate = Mockery::mock(Gate::class);
        $gate->shouldReceive('has')->with('tusupload-can-upload')->once()->andReturn(true);
        $gate->shouldReceive('forUser')->once()->andReturn($gate);
        $gate->shouldReceive('denies')->once()->andReturn(false);

        //mock UserProvider
        $userProvider = Mockery::mock(UserProvider::class);
        $userProvider->shouldReceive('retrieveByCredentials')->with($credentials)->once()->andReturn($expected_user);

        $resolver = new AuthenticationResolver($gate, $userProvider);

        $is_valid = $resolver->validate($credentials, $tus_payload_metadata);

        $this->assertTrue($is_valid);
        $this->assertNotNull($resolver->user());
        $this->assertEquals($expected_user, $resolver->user());
    }

    /** @test */
    public function authentication_resolver_do_not_validate_user()
    {
        $credentials = ['api_token' => 'A1'];
        $tus_payload_metadata = new \stdClass;
        $expected_user = 'foo';
        
        //mock Gate
        $gate = Mockery::mock(Gate::class);
        $gate->shouldReceive('has')->with('tusupload-can-upload')->once()->andReturn(true);
        $gate->shouldReceive('forUser')->once()->andReturn($gate);
        $gate->shouldReceive('denies')->once()->andReturn(true);

        //mock UserProvider
        $userProvider = Mockery::mock(UserProvider::class);
        $userProvider->shouldReceive('retrieveByCredentials')->with($credentials)->once()->andReturn($expected_user);

        $resolver = new AuthenticationResolver($gate, $userProvider);

        $is_valid = $resolver->validate($credentials, $tus_payload_metadata);

        $this->assertFalse($is_valid);
        $this->assertNull($resolver->user());
    }

    /** @test */
    public function authentication_resolver_do_not_retrieve_user()
    {
        $credentials = ['api_token' => 'A1'];
        $tus_payload_metadata = new \stdClass;
        
        //mock Gate
        $gate = Mockery::mock(Gate::class);
        $gate->shouldReceive('has')->never();
        $gate->shouldReceive('forUser')->never();
        $gate->shouldReceive('denies')->never();

        //mock UserProvider
        $userProvider = Mockery::mock(UserProvider::class);
        $userProvider->shouldReceive('retrieveByCredentials')->with($credentials)->once()->andReturn(null);

        $resolver = new AuthenticationResolver($gate, $userProvider);

        $is_valid = $resolver->validate($credentials, $tus_payload_metadata);

        $this->assertFalse($is_valid);
        $this->assertNull($resolver->user());
    }
    
    
}
