<?php

namespace OneOffTech\TusUpload\Http\Controllers;

use OneOffTech\TusUpload\TusUpload;
use Illuminate\Http\Request;
use OneOffTech\TusUpload\TusUploadRepository;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use OneOffTech\TusUpload\Http\Requests\CreateUploadRequest;
use OneOffTech\TusUpload\Events\TusUploadCancelled;

class TusUploadQueueController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var \OneOffTech\TusUpload\TusUploadRepository
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
     * @param  OneOffTech\TusUpload\Http\Requests\CreateUploadRequest  $request
     * @return \Illuminate\Http\Response|OneOffTech\TusUpload\TusUpload
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $upload)
    {

        $cancelled_upload = $this->uploads->cancel($this->uploads->findByUploadRequest($request->user(), $upload));

        event(new TusUploadCancelled($cancelled_upload));

        return response()->json($cancelled_upload);

    }
}
