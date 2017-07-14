<?php

namespace Avvertix\TusUpload\Auth;

use Avvertix\TusUpload\Contracts\AuthenticationResolver as AuthenticationResolverContract;
use Illuminate\Foundation\Auth\User;

class AuthenticationResolverFake implements AuthenticationResolverContract
{
    /**
     * @inherit
     */
    public function validate($credentials, $object)
    {
        
        return true;

    }

    /**
     * @inherit
     */
    public function user()
    {
        $user = new User();
        $user->id = 1;
        return $user;
    }
}