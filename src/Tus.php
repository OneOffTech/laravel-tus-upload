<?php

namespace OneOffTech\TusUpload;

use Illuminate\Support\Facades\Route;

class Tus
{

    /**
     * Binds the Tus Upload routes
     *
     * @return void
     */
    public static function routes()
    {
        Route::group([
            'prefix' => '',
            'namespace' => 'OneOffTech\TusUpload\Http\Controllers',
            'middleware' => 'web',
        ], function ($router) {

            $router->resource(
                '/uploadjobs', 
                'TusUploadQueueController', 
                [
                    'only' => [
                        'index', 'store', 'destroy'
                    ],
                    'names' => [
                        'index' => 'tus.jobs.index',
                        'store' => 'tus.jobs.store',
                        'destroy' => 'tus.jobs.destroy',
                    ]
                ]
            );

        });

    }
}