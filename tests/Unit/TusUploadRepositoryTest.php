<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Avvertix\TusUpload\TusUpload;
use Avvertix\TusUpload\Events\TusUploadStarted;
use Avvertix\TusUpload\Events\TusUploadProgress;
use Avvertix\TusUpload\Events\TusUploadCompleted;
use Avvertix\TusUpload\Events\TusUploadCancelled;

class TusUploadRepositoryTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function tus_upload_is_created_and_started_event_dispatched()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $requestID = str_random(60);

        $upload = $repository->create(1, $requestID, 'test.pdf', 100);

        Event::assertDispatched(TusUploadStarted::class, function ($e) use ($upload) {
            return $e->upload->id === $upload->id &&
                   $e->upload->user_id === $upload->user_id &&
                   $e->upload->filename === $upload->filename &&
                   $e->upload->request_id === $upload->request_id &&
                   $e->upload->tus_id == null &&
                   $e->upload->size === $upload->size &&
                   $e->upload->offset === $upload->offset;
        });

    }

    /** @test */
    public function tus_upload_is_updated_and_progress_event_dispatched()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100
        ]);

        $upload->save();

        $upload = $repository->update($upload, 25);

        Event::assertDispatched(TusUploadProgress::class, function ($e) use ($upload) {
            return $e->upload->id === $upload->id &&
                   $e->upload->user_id === $upload->user_id &&
                   $e->upload->filename === $upload->filename &&
                   $e->upload->tus_id === $upload->tus_id &&
                   $e->upload->size === $upload->size &&
                   $e->upload->offset === $upload->offset;
        });
    }

    /** @test */
    public function tus_upload_update_is_discarded_if_upload_is_already_completed()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'completed' => true
        ]);

        $upload->save();

        $upload = $repository->update($upload, str_random(60), 25);

        Event::assertNotDispatched(TusUploadProgress::class);
    }

    /** @test */
    public function tus_upload_update_is_discarded_if_upload_is_already_cancelled()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'cancelled' => true
        ]);

        $upload->save();

        $upload = $repository->update($upload, str_random(60), 25);

        Event::assertNotDispatched(TusUploadProgress::class);
    }

    /** @test */
    public function tus_upload_mark_as_completed_and_completed_event_dispatched()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'cancelled' => false,
            'completed' => false,
        ]);

        $upload->save();

        $upload = $repository->complete($upload);

        $this->assertTrue($upload->completed());
        $this->assertFalse($upload->cancelled());
        $this->assertEquals($upload->size, $upload->offset, 'offset and size are not equal');

        Event::assertDispatched(TusUploadCompleted::class, function ($e) use ($upload) {
            return $e->upload->id === $upload->id &&
                   $e->upload->user_id === $upload->user_id &&
                   $e->upload->filename === $upload->filename &&
                   $e->upload->tus_id === $upload->tus_id &&
                   $e->upload->size === $upload->size &&
                   $e->upload->cancelled === false &&
                   $e->upload->completed === true &&
                   $e->upload->offset === $upload->offset;
        });
    }

    /** @test */
    public function tus_upload_mark_as_cancelled_and_cancelled_event_dispatched()
    {
        Event::fake();

        $repository = app('Avvertix\TusUpload\TusUploadRepository');

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => str_random(60),
            'filename' => 'test.pdf',
            'size' => 100,
            'cancelled' => false,
            'completed' => false,
        ]);

        $upload->save();

        $upload = $repository->cancel($upload);

        $this->assertTrue($upload->cancelled());
        $this->assertFalse($upload->completed());

        Event::assertDispatched(TusUploadCancelled::class, function ($e) use ($upload) {
            return $e->upload->id === $upload->id &&
                   $e->upload->user_id === $upload->user_id &&
                   $e->upload->filename === $upload->filename &&
                   $e->upload->tus_id === $upload->tus_id &&
                   $e->upload->size === $upload->size &&
                   $e->upload->cancelled === true &&
                   $e->upload->completed === false &&
                   $e->upload->offset === $upload->offset;
        });
    }
}
