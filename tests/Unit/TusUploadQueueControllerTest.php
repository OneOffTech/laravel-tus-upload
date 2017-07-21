<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Auth\Access\Gate;
use Avvertix\TusUpload\TusUpload;
use Avvertix\TusUpload\Http\Controllers\TusUploadQueueController;
use Avvertix\TusUpload\Events\TusUploadStarted;
use Avvertix\TusUpload\Events\TusUploadProgress;
use Avvertix\TusUpload\Events\TusUploadCompleted;
use Avvertix\TusUpload\Events\TusUploadCancelled;
use Avvertix\TusUpload\Http\Requests\CreateUploadRequest;
use Mockery;

class TusUploadQueueControllerTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function upload_queue_entry_is_created_and_token_is_returned()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $requestId = str_random(60);
        $args = ['id' => $requestId, 'filename' => 'test.pdf', 'filesize' => 5];

        $base_request = CreateUploadRequest::createFromBase(\Symfony\Component\HttpFoundation\Request::create('/uploadqueue', 'POST', $args));
        $request = Mockery::mock($base_request);

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->store($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

        $original = $response->getOriginalContent();

        $this->assertEquals($requestId, $original['request_id']);
        $this->assertEquals($args['filename'], $original['filename']);
        $this->assertEquals($args['filesize'], $original['size']);
        $this->assertNotEmpty($original['upload_token']);
        $this->assertNotEmpty($original['location']);
    }


    /** @test */
    public function upload_queue_is_returned_for_the_user()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => \Carbon\Carbon::now()->addHour()
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
            'upload_token' => str_random(60),
            'upload_token_expires_at' => \Carbon\Carbon::now()->addHour()
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
            'upload_token' => str_random(60),
            'upload_token_expires_at' => \Carbon\Carbon::now()->addHour()
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
