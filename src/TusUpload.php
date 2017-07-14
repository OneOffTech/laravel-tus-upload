<?php

namespace Avvertix\TusUpload;

use Illuminate\Database\Eloquent\Model;

/**
 * An upload that is in progress via the Tus protocol.
 *
 * For accessing and manipulating models {@see \Avvertix\TusUpload\TusUploadRepository}
 */
class TusUpload extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tus_uploads_queue';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'json',
        'cancelled' => 'bool',
        'completed' => 'bool',
    ];

    /**
     * Determine if the upload is completed.
     *
     * @return bool
     */
    public function completed()
    {
        return @$this->attributes['completed'] ?: false;
    }

    /**
     * Determine if the upload has been cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return @$this->attributes['cancelled'] ?: false;
    }


    /**
     * Get the uploaded file.
     *
     * @return ...
     */
    public function file()
    {
        
    }

    /**
     * Get the uploaded file path on disk.
     *
     * @return string
     */
    public function path()
    {

    }
}