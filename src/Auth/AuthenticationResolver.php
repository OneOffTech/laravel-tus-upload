<?php

namespace Avvertix\TusUpload\Auth;

use Avvertix\TusUpload\Contracts\AuthenticationResolver as AuthenticationResolverContract;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\UserProvider;

class AuthenticationResolver implements AuthenticationResolverContract
{
    /**
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    private $gate = null;
    
    /**
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    private $userProvider = null;

    /**
     * The user, if authenticated
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    private $user = null;

    public function __construct(Gate $gate, UserProvider $userProvider)
    {
        $this->gate = $gate;
        $this->userProvider = $userProvider;
    }

    /**
     * @inherit
     */
    public function validate($credentials, $object) {

        // $credentials = ['api_token' => $token];

        $user = $this->userProvider->retrieveByCredentials($credentials);

        if (is_null($user)) {
            // user is not valid
            return false; // maybe throw exceptions and then handle them properly
        }

        // if there is a gate defined, then invoke it to check if the user can do the upload
        if ($this->gate->has('tusupload-can-upload') && $this->gate->forUser($user)->denies('tusupload-can-upload', $object)) {
            // returning false to block the upload, if the Gate is not allowing the user to perform it
            return false;
        }

        $this->user = $user;

        return true;

    }


    /**
     * @inherit
     */
    public function user()
    {
        return $this->user;
    }
}