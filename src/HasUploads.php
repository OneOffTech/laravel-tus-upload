<?php

namespace OneOffTech\TusUpload;

use OneOffTech\TusUpload\TusUpload;

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