<?php

namespace Avvertix\TusUpload\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TusHookInput
{

    /**
     * The data contained in this request
     *
     * @var array
     */
    private $data = [];

    /**
     * Creates an hook request from the hook payload.
     * The hook payload is expected to be a string 
     * containing a json encoded object
     *
     * @param string $hookPayload
     * @return TusHookInput
     */
    public static function create($hookPayload)
    {
        $request = new static;
        
        $request->data = json_decode($hookPayload, true);

        return $request;
    }




    /**
     * Determine if the request contains a given input item key.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the given input key is an empty string for "has".
     *
     * @param  string  $key
     * @return bool
     */
    protected function isEmptyString($key)
    {
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    /**
     * Get all of the input and files for the request.
     *
     * @return array
     */
    public function all()
    {
        return $this->input();
    }

    /**
     * Retrieve an input item from the request.
     *
     * @param  string  $key
     * @param  string|array|null  $default
     * @return string|array
     */
    public function input($key = null, $default = null)
    {
        return data_get(
            $this->data, $key, $default
        );
    }

    /**
     * Retrieve the upload request id.
     *
     * @return string
     */
    public function id()
    {
        return $this->input('MetaData.upload_request_id');
    }

    /**
     * Retrieve the Tus file id.
     *
     * @return string|null
     */
    public function tusId()
    {
        return $this->input('ID', null);
    }


    public function __get($property)
    {
        return $this->input($property);
    }

    // /**
    //  * Get the validation rules that apply to the request.
    //  *
    //  * @return array
    //  */
    // public function rules()
    // {
    //     return [
    //         //
    //     ];
    // }
}
