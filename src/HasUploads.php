<?php

namespace Avvertix\Uploaddy;

use Illuminate\Container\Container;
use Avvertix\TusUpload\TusUpload;

trait HasUploads
{
    /**
     * Get all of the user's uploads.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function uploads()
    {
        return $this->hasMany(TusUpload::class, 'user_id');
    }
}