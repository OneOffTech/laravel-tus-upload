<?php

namespace Avvertix\TusUpload\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TusUploaderStopped implements ShouldQueue
{
    use SerializesModels;

    /**
     * @var string
     */
    public $reason;
    
    /**
     * @var mixed|null
     */
    public $error;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

}
