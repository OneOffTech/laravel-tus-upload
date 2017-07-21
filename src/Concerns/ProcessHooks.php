<?php

namespace Avvertix\TusUpload\Concerns;

use Illuminate\Container\Container;
use Avvertix\TusUpload\TusUpload;
use Avvertix\TusUpload\Console\TusHookInput;
use Log;
use Exception;

trait ProcessHooks
{
    /**
     * Validates the hook payload
     *
     * Currently checks for the request id, the filename and the token fields
     *
     * @return bool
     */
    private function isPayloadValid($payload){

        return $payload->has('MetaData.filename')
               && $payload->has('MetaData.token')
               && !empty($payload->id());
    }

    /**
     * Process the pre-create hook
     */
    private function preCreate(TusHookInput $payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        if(is_null($upload)){
            Log::info("Upload identified by {$requestId}-{$token} not existing.");
            throw new Exception('Upload not found, continuation not granted');
        }

        return true; 
    }

    /**
     * Process the post-receive hook
     */
    private function postReceive(TusHookInput $payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        // let's update the status of the upload
        $this->uploads->update($upload, $payload->tusId(), $payload->input('Offset'));

        return true;
    }

    /**
     * Process the post-finish hook
     */
    private function postFinish(TusHookInput $payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        if(is_null($upload)){
            Log::error("Upload {$requestId}-{$token} not found.");
            return false;
        }

        $this->uploads->complete($upload);

        return true;
    }

    /**
     * Process the post-terminate hook
     */
    private function postTerminate(TusHookInput $payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        if(is_null($upload)){
            Log::error("Upload {$requestId}-{$token} not found.");
            return false;
        }

        $this->uploads->cancel($upload);

        return true;
    }
}