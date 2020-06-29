<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Auth\Access\Gate;
use OneOffTech\TusUpload\TusUpload;
use OneOffTech\TusUpload\Http\Controllers\TusUploadQueueController;
use OneOffTech\TusUpload\Events\TusUploadStarted;
use OneOffTech\TusUpload\Events\TusUploadProgress;
use OneOffTech\TusUpload\Events\TusUploadCompleted;
use OneOffTech\TusUpload\Events\TusUploadCancelled;
use OneOffTech\TusUpload\Http\Requests\CreateUploadRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Mockery;

class TusUploadQueueControllerTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function upload_queue_entry_is_created_and_token_is_returned()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $requestId = Str::random(60);
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
    public function upload_queue_entry_stores_metadata()
    {
        $this->withoutMiddleware();
        
        $controller = app(TusUploadQueueController::class);

        $requestId = Str::random(60);
        $args = ['id' => $requestId, 'filename' => 'test.pdf', 'filesize' => 5, 'collection' => 5, 'filetype' => 'application/pdf'];

        $base_request = CreateUploadRequest::createFromBase(\Symfony\Component\HttpFoundation\Request::create('/uploadqueue', 'POST', $args));
        $request = Mockery::mock($base_request);

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->store($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

        $upload = TusUpload::where('request_id', $requestId)->first();

        $this->assertNotNull($upload);
        $this->assertEquals($args['filename'], $upload->filename);
        $this->assertEquals($args['filesize'], $upload->size);
        $this->assertEquals($args['filetype'], $upload->mimetype);
        $this->assertNotNull($upload->metadata);
        $this->assertEquals(['collection' => 5], $upload->metadata);
    }


    /** @test */
    public function upload_queue_is_returned_for_the_user()
    {
        $this->withoutMiddleware();

        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => Str::random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'upload_token' => Str::random(60),
            'upload_token_expires_at' => \Carbon\Carbon::now()->addHour()
        ]);

        $upload->save();

        // refreshing to get the upload id
        $upload = $upload->fresh();

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->index($request);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);

        $this->assertEquals(json_encode(Arr::wrap($upload)), $response->getContent());
    }

    /** @test */
    public function upload_queue_item_can_be_cancelled()
    {
        $this->withoutMiddleware();
        
        Event::fake();
        
        $controller = app(TusUploadQueueController::class);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => Str::random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'cancelled' => false,
            'completed' => false,
            'upload_token' => Str::random(60),
            'upload_token_expires_at' => \Carbon\Carbon::now()->addHour()
        ]);

        $upload->save();

        // refreshing to get the upload id
        $upload = $upload->fresh();

        $request = Mockery::mock('Illuminate\Http\Request');

        $request->shouldReceive('user')->andReturn(1);

        $response = $controller->destroy($request, $upload->request_id);

        $cancelled_upload = $upload->fresh();

        $this->assertTrue($cancelled_upload->cancelled);
        $this->assertInstanceOf(Carbon::class, $cancelled_upload->cancelled_at);
        $this->assertJson($response->getContent());

        Event::assertDispatched(TusUploadCancelled::class, function ($e) use ($cancelled_upload) {
            return $e->upload->id === $cancelled_upload->id;
        });
    }
 
}
