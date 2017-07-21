<?php

namespace Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPUnit\Framework\Assert as PHPUnit;

class FakeUser extends Authenticatable
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    private $calls = [];


    /**
     * Determine if the entity has a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {

        if(isset($this->calls['can'])){
            array_push($this->calls['can'], compact('ability', 'arguments'));
        }
        else {
            $this->calls['can'] = [compact('ability', 'arguments')];
        }

        return true;
    }


    private function called($method){
        return isset($this->calls[$method]) ? collect($this->calls[$method]) : collect([]);
    }


    public function assertCanCalled($ability)
    {
        PHPUnit::assertTrue(
            $this->called('can')->where('ability', $ability)->count() > 0,
            "The expected [can({$ability})] method was not called."
        );
    }
}
