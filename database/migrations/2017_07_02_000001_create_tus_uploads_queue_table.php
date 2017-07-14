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
            $table->integer('user_id')->index()->unsigned(); // used as key for connecting the upload to the user 
                                                             // that is performing it
            $table->string('request_id')->index(); // the identifier of the client request, used to retrieve 
                                                   // the queue entry and keep it in sync with the client
            $table->string('tus_id')->unique()->nullable(); // the identifier that Tus associate with the upload
            $table->string('filename');
            $table->string('mimetype')->nullable();
            $table->json('metadata')->nullable(); //can be used for application specific metadata
            $table->unsignedBigInteger('size'); // The upload's total size in bytes.
            $table->unsignedBigInteger('offset')->default(0); // The upload's current offset in bytes.
            $table->boolean('cancelled')->default(false); // the upload has been cancelled by the user
            $table->boolean('completed')->default(false);
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