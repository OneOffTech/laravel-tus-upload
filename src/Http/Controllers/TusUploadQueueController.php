<?php

namespace Avvertix\TusUpload\Http\Controllers;

use Avvertix\TusUpload\TusUpload;
use Illuminate\Http\Request;
use Avvertix\TusUpload\TusUploadRepository;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Avvertix\TusUpload\Http\Requests\CreateUploadRequest;

class TusUploadQueueController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var \Avvertix\TusUpload\TusUploadRepository
     */
    private $uploads = null;

    public function __construct(TusUploadRepository $uploads)
    {
        $this->uploads = $uploads;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        return response()->json($this->uploads->forUser($request->user()));
    }


    /**
     * Creates an entry in the upload queue.
     *
     * Checks if the user can do the upload and returns the token 
     * for the real upload
     *
     * @param  Avvertix\TusUpload\Http\Requests\CreateUploadRequest  $request
     * @return \Illuminate\Http\Response|Avvertix\TusUpload\TusUpload
     */
    public function store(CreateUploadRequest $request)
    {

        $upload = $this->uploads->create(
            $request->user(), 
            $request->input('id'), 
            $request->input('filename'), 
            (int)$request->input('filesize'),
            $request->input('filetype', null),
            0,
            $request->except(['id', 'filename', 'filesize', 'filetype']));


        $data = [
            'request_id' => $upload->request_id,
            'upload_token' => $upload->upload_token,
            'filename' => $upload->filename,
            'size' => $upload->size,
            'location' => tus_url(),
        ];

        return response()->json($data);

    }
    
    /**
     * Remove the specified resource from storage.
     *
     * It can be done only if the upload is terminated (either 
     * because completed or cancelled)
     *
     * @param  \Avvertix\TusUpload\TusUpload  $upload
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, TusUpload $upload)
    {

        $done = $this->uploads->delete($upload);

        if(!$done){
            return response()->json(['success' => false, 'error' => 'Not deletable because not completed or cancelled.']);
        }

        return response()->json(['success' => 'ok']);

    }
}
