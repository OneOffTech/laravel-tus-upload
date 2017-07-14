<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Avvertix\TusUpload\TusUpload;
use Avvertix\TusUpload\Http\Controllers\TusUploadQueueController;
use Avvertix\TusUpload\Events\TusUploadStarted;
use Avvertix\TusUpload\Events\TusUploadProgress;
use Avvertix\TusUpload\Events\TusUploadCompleted;
use Avvertix\TusUpload\Events\TusUploadCancelled;
use Mockery;

class TusUploadQueueControllerTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function upload_queue_is_returned_for_the_user()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100
        ]);

        $upload->save();

        // refreshing to get the upload id
        $upload = $upload->fresh();

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->index($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

        $this->assertEquals(json_encode(array_wrap($upload)), $response->getContent());
    }

    /** @test */
    public function upload_queue_item_can_be_deleted()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'cancelled' => true,
            'completed' => false,
        ]);

        $upload->save();

        // refreshing to get the upload id
        $upload = $upload->fresh();

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->destroy($request, $upload);

        $this->assertEquals(json_encode(['success' => 'ok']), $response->getContent());
    }

    /** @test */
    public function upload_queue_item_that_is_in_progress_cannot_be_deleted()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'cancelled' => false,
            'completed' => false,
        ]);

        $upload->save();

        // refreshing to get the upload id
        $upload = $upload->fresh();

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->destroy($request, $upload);

        $this->assertNotEquals(json_encode(['success' => 'ok']), $response->getContent());
    }

 
}
