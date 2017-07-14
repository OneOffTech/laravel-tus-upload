<?php

namespace Avvertix\TusUpload\Http\Controllers;

use Avvertix\TusUpload\TusUpload;
use Illuminate\Http\Request;
use Avvertix\TusUpload\TusUploadRepository;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;

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
     * Remove the specified resource from storage.
     *
     * It can be done only if the upload is terminated (either 
     * because completed or cancelled)
     *
     * @param  \Avvertix\Uploaddy\TusUpload  $upload
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
