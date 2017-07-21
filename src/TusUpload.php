<?php

namespace Avvertix\TusUpload;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * An upload that is in progress via the Tus protocol.
 *
 * For accessing and manipulating models {@see \Avvertix\TusUpload\TusUploadRepository}
 *
 * @property int $id the autoincrement identifier of the upload
 * @property int $user_id the user identifier that is performing the upload
 * @property string $request_id the request identifier, set by the client to identify the upload in the queue
 * @property string $tus_id the identifier that the Tusd server generate for the file upload
 * @property string $upload_token the token that authorize the upload
 * @property /Carbon/Carbon $upload_token_expires_at the expiration date of the $upload_token
 * @property string $filename the name of the file that is being uploaded
 * @property string $mimetype the file mimetype (can be null)
 * @property json|mixed $metadata additional application dependent metadata (can be null)
 * @property int $size the size, in bytes, of the file
 * @property int $offset the uploaded bytes
 * @property /Carbon/Carbon $cancelled_at when the upload was cancelled
 * @property /Carbon/Carbon $completed_at when the upload was succesfully completed
 * @property /Carbon/Carbon $created_at when the upload was created
 * @property /Carbon/Carbon $updated_at when any attribute of the upload was lastly modified
 * @property boolean $completed indicates if the upload has been completed
 * @property boolean $cancelled indicates if the upload has been cancelled
 * @property-read boolean $started  indicates if the tus protocol started the upload of the file. Checks if the $tus_id is set
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
    protected $hidden = ['user_id', 'upload_token_expires_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'cancelled_at',
        'completed_at'
    ];


    /**
     * Set the completed attribute
     *
     * @param  bool  $completed
     * @return void
     */
    public function setCompletedAttribute($completed)
    {
        if ($completed && ! $this->completed_at) {
            $this->attributes['completed_at'] = Carbon::now();
        }

        if (! $completed && $this->completed_at) {
            $this->attributes['completed_at'] = null;
        }
    }

    /**
     * Get if the upload is complete.
     *
     * @param  mixed  $value not taken into account
     * @return bool
     */
    public function getCompletedAttribute($value = null)
    {
        return isset($this->attributes['completed_at']) && !is_null($this->attributes['completed_at']);
    }

    /**
     * Set the cancelled attribute
     *
     * @param  bool  $cancelled
     * @return void
     */
    public function setCancelledAttribute($cancelled)
    {
        if ($cancelled && ! $this->cancelled_at) {
            $this->attributes['cancelled_at'] = Carbon::now();
        }

        if (! $cancelled && $this->cancelled_at) {
            $this->attributes['cancelled_at'] = null;
        }
    }

    /**
     * Get if the upload was cancelled.
     *
     * @param  mixed  $value not taken into account
     * @return bool
     */
    public function getCancelledAttribute($value = null)
    {
        return isset($this->attributes['cancelled_at']) && !is_null($this->attributes['cancelled_at']);
    }

    /**
     * Get if the upload is started.
     *
     * @param  mixed  $value not taken into account
     * @return bool
     */
    public function getStartedAttribute($value = null)
    {
        return isset($this->attributes['tus_id']) && !is_null($this->attributes['tus_id']);
    }



    /**
     * Determine if the upload is completed.
     *
     * @return bool
     * @see getCompletedAttribute()
     */
    public function completed()
    {
        return $this->completed;
    }

    /**
     * Determine if the upload has been cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return $this->cancelled;
    }

    /**
     * Determine if the upload has been started.
     *
     * @return bool
     */
    public function started()
    {
        return $this->started;
    }

    /**
     * Get the path (on disk) of the uploaded file.
     *
     * @return string|null the path of the file being uploaded on disk, if upload is in progress, null otherwise
     */
    public function path()
    {
        return $this->started ? config('tusupload.storage').'/'.$this->tus_id : null;
    }
}