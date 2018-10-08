<?php

namespace OneOffTech\TusUpload\Concerns;

use Illuminate\Container\Container;
use OneOffTech\TusUpload\TusUpload;
use OneOffTech\TusUpload\Console\TusHookInput;
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
        Log::info("Processing preCreate...", ['payload' => $payload->__toString()]);

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

        try{
        
            $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

            if(is_null($upload)){
                return true;
            }

            if(is_null($upload->tus_id)){
                // first progress update, we get the id and the first offset information
                $this->uploads->updateTusIdAndProgress($upload, $payload->tusId(), $payload->input('Offset', 0));
            }
            else {
                $currentPercent = ($payload->input('Offset') * 100) / $upload->size;
                $savedPercent = ($upload->offset * 100) / $upload->size;
                
                // subsequent progress events, we update the entry in the database only if
                if($payload->input('Offset') > $upload->offset && $currentPercent > ($savedPercent + 10)){

                    Log::info("Processing postReceive...", ['payload' => $payload->__toString()]);

                    // let's update the status of the upload
                    $this->uploads->updateProgress($upload, $payload->input('Offset'));
                }
            }
            
        }catch(Exception $ex){
            Log::error("Processing postReceive error.", ['ex' => $ex]);
        }

        return true;
    }

    /**
     * Process the post-finish hook
     */
    private function postFinish(TusHookInput $payload)
    {
        Log::info("Processing postFinish...", ['payload' => $payload->__toString()]);

        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        if(is_null($upload)){
            Log::error("Tus post finish, upload {$requestId}-{$token} not found.");
            return false;
        }

        if(is_null($upload->tus_id)){
            $this->uploads->updateTusId($upload, $payload->tusId());
        }

        $this->uploads->complete($upload);

        return true;
    }

    /**
     * Process the post-terminate hook
     */
    private function postTerminate(TusHookInput $payload)
    {
        Log::info("Processing postTerminate...", ['payload' => $payload->__toString()]);

        $requestId = $payload->id();
        $token = $payload->input('MetaData.token');
        
        $upload = $this->uploads->findByUploadRequestAndToken($requestId, $token);

        if(is_null($upload)){
            Log::error("Upload {$requestId}-{$token} not found.");
            return false;
        }

        if(is_null($upload->tus_id)){
            $this->uploads->updateTusId($upload, $payload->tusId());
        }

        $this->uploads->cancel($upload);

        return true;
    }
}