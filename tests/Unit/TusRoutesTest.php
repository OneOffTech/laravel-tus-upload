<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Avvertix\TusUpload\TusUpload;
use Avvertix\TusUpload\Tus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

class TusRoutesTest extends AbstractTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function tus_routes_are_registered()
    {
        Tus::routes();

        $this->assertTrue(Route::has('tus.jobs.index'));
        $this->assertTrue(Route::has('tus.jobs.store'));
        $this->assertTrue(Route::has('tus.jobs.destroy'));
    }

}
