<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Avvertix\TusUpload\TusUpload;
use Carbon\Carbon;

class TusUploadTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function tus_upload_file_path_is_returned_if_upload_is_started()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'tus_id' => $tusId,
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertEquals(config('tusupload.storage').'/'.$tusId, $upload->path());

    }
    
    /** @test */
    public function tus_upload_file_path_is_not_returned_when_upload_is_not_started()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertNull($upload->path());

    }
    
    /** @test */
    public function tus_upload_started()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertFalse($upload->started());
        $this->assertFalse($upload->started);
        
        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'tus_id' => $tusId,
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertTrue($upload->started());
        $this->assertTrue($upload->started);

    }

      
    /** @test */
    public function tus_upload_metadata_is_stored_as_json()
    {

        $tusId = str_random(10);

        $metadata_original = ['key' => 'value'];

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => $metadata_original,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertTrue(is_array($upload->metadata));
        $this->assertEquals($metadata_original, $upload->metadata);

    }
      
    /** @test */
    public function tus_upload_is_started()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'tus_id' => $tusId,
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
        ]);


        $this->assertTrue($upload->started);
        $this->assertTrue($upload->started());

    }
      
    /** @test */
    public function tus_upload_is_cancelled()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'tus_id' => $tusId,
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
            'cancelled_at' => Carbon::now(),
        ]);


        $this->assertTrue($upload->cancelled);
        $this->assertTrue($upload->cancelled());

    }
      
    /** @test */
    public function tus_upload_is_completed()
    {

        $tusId = str_random(10);

        $upload = (new TusUpload)->forceFill([
            'user_id' => 1,
            'request_id' => 'A1',
            'tus_id' => $tusId,
            'filename' => 'test.pdf',
            'size' => 100,
            'offset' => 10,
            'mimetype' => null,
            'metadata' => null,
            'upload_token' => str_random(60),
            'upload_token_expires_at' => Carbon::now()->addHour(),
            'completed_at' => Carbon::now(),
        ]);


        $this->assertTrue($upload->completed);
        $this->assertTrue($upload->completed());

    }

    
}
