<?php

namespace Avvertix\TusUpload\Concerns;

use Illuminate\Container\Container;
use Avvertix\TusUpload\TusUpload;
use Log;

trait ProcessHooks
{
    /**
     * Check for the existence of the user and filename property in the metadata
     *
     * todo: more checks
     */
    private function isPayloadValid($payload){

        return $payload->has('MetaData.filename')
               && $payload->has('MetaData.api_token')
               && !empty($payload->id());
    }


    private function preCreate($payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.api_token');
     
        $credentials = ['api_token' => $token];

        if (!$this->auth->validate($credentials, $payload->MetaData)) {
            // user authentication not valid or the user don't have permission to upload files
            return false; // maybe throw exceptions and then handle them properly
        }
        
        $userId = $this->auth->user()->id;
        
        $upload = $this->uploads->findByUploadRequest($userId, $requestId);

        if(is_null($upload)){
            Log::info("Upload {$userId}-{$requestId} not existing, creating the entry.");
            $this->uploads->create(
                $userId, 
                $requestId, 
                $payload->input('MetaData.filename'), 
                $payload->Size, 
                null, // $payload->MetaData->mimetype, 
                $payload->Offset, 
                $payload->MetaData);
        }
        else {

            // an Upload with the same requestID and userId is in progress
            // better to say no and generate a new requestID, probable collision

            return false;
        }

        return true; 
    }

    private function postReceive($payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.api_token');
     
        $credentials = ['api_token' => $token];

        if (!$this->auth->validate($credentials, $payload->MetaData)) {
            // user authentication not valid or the user don't have permission to upload files
            return false; // maybe throw exceptions and then handle them properly
        }
        
        $userId = $this->auth->user()->id;
        
        $upload = $this->uploads->findByUploadRequest($userId, $requestId);

        // let's update the status of the upload
        $this->uploads->update($upload, $payload->tusId(), $payload->input('Offset'));

        return true;
    }

    private function postFinish($payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.api_token');
     
        $credentials = ['api_token' => $token];

        if (!$this->auth->validate($credentials, $payload->MetaData)) {
            // user authentication not valid or the user don't have permission to upload files
            return false; // maybe throw exceptions and then handle them properly
        }
        
        $userId = $this->auth->user()->id;
        
        $upload = $this->uploads->findByUploadRequest($userId, $requestId);

        if(is_null($upload)){
            Log::error("Upload {$userId}-{$requestId} not found.");
            return false;
        }

        $this->uploads->complete($upload);

        return true;
    }

    private function postTerminate($payload)
    {
        $requestId = $payload->id();
        $token = $payload->input('MetaData.api_token');
     
        $credentials = ['api_token' => $token];

        if (!$this->auth->validate($credentials, $payload->MetaData)) {
            // user authentication not valid or the user don't have permission to upload files
            return false; // maybe throw exceptions and then handle them properly
        }
        
        $userId = $this->auth->user()->id;
        
        $upload = $this->uploads->findByUploadRequest($userId, $requestId);

        if(is_null($upload)){
            Log::error("Upload {$userId}-{$requestId} not found.");
            return false;
        }

        $this->uploads->cancel($upload);

        return true;
    }
}