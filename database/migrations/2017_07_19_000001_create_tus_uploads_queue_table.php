<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTusUploadsQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tus_uploads_queue', function (Blueprint $table) {
            $table->increments('id');

            // used as key for connecting the upload to the user that is performing it
            $table->integer('user_id')->index()->unsigned(); 

            // the identifier of the client request, used by the client to identify each upload in the queue
            $table->string('request_id')->index(); 
            
            // Authentication token that will verify the hook coming from tusd
            $table->string('upload_token', 60)->index()->unique(); 
            $table->timestamp('upload_token_expires_at'); 
                                                   
            // the identifier that Tus associate with the upload, known only after the first post-* hook
            $table->string('tus_id')->unique()->nullable(); 
            
            $table->string('filename');
            
            $table->string('mimetype')->nullable();
            
            //can be used for application specific metadata, not using json column type because MariaDB 10 don't supports it
            $table->text('metadata')->nullable();
            
            // The upload's total size in bytes.
            $table->unsignedBigInteger('size'); 
            
            // The upload's current offset in bytes.
            $table->unsignedBigInteger('offset')->default(0);

            // the upload has been cancelled by the user
            $table->timestamp('cancelled_at')->nullable();

            // upload completed. Storing the time to have much more information, 
            // inspired by https://blog.jerguslejko.com/post/refactoring-series-checkboxes-and-timestamps
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tus_uploads_queue');
    }
}