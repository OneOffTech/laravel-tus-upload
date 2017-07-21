<?php


if (! function_exists('tus_url')) {
    /**
     * Output the public URL of the tusd server endpoint.
     *
     * @return string the tusd absolute URL
     *
     * @throws \InvalidArgumentException if asset is not found in elixir manifest
     */
    function tus_url()
    {
        $is_proxied = config('tusupload.behind_proxy');
        $public_url = config('tusupload.public_url');
        $host = config('tusupload.host');
        $port = config('tusupload.port');
        $base_path = config('tusupload.base_path');
        
        if ($is_proxied) {
            return $public_url;
        }
        
        return (starts_with('https', url('/')) ? 'https://' : 'http://') . $host . ':' . $port . $base_path;
    }
}