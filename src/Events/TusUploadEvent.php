<?php

namespace OneOffTech\TusUpload\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use OneOffTech\TusUpload\TusUpload;

class TusUploadEvent implements ShouldQueue
{
    use SerializesModels;

    /**
     * @var \OneOffTech\TusUpload\TusUpload
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
