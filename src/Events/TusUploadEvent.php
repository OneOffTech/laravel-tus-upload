<?php

namespace Avvertix\TusUpload\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Avvertix\TusUpload\TusUpload;

class TusUploadEvent implements ShouldQueue
{
    use SerializesModels;

    /**
     * @var \Avvertix\TusUpload\TusUpload
     */
    public $upload;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(TusUpload $upload)
    {
        $this->upload = $upload;
    }

}
