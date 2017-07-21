<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Uploaddy API Routes
|--------------------------------------------------------------------------
|
| Here is where the Uploaddy routes are registered. Routes are used for
| handling Tus server hooks and UI progress visualization
|
*/

Route::middleware('web')
       ->resource('/uploadjobs', \Avvertix\TusUpload\Http\Controllers\TusUploadQueueController::class, [
            'only' => [
                'index', 'store', 'destroy'
            ]
]);
