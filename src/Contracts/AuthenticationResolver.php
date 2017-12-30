<?php

namespace OneOffTech\TusUpload\Contracts;

interface AuthenticationResolver
{
    /**
     * Get an upload by the given ID.
     *
     * @param  int  $id
     * @return boolean true if the authentication passed, false otherwise
     */
    public function validate($credentials, $object);


    /**
     * Get the Authenticatable entity that passed the validation.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null the user that corresponds to the credentials or null if the validation failed or was not performed.
     */
    public function user();
}