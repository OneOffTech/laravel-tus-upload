<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use Tests\FakeUser;
use OneOffTech\TusUpload\Http\Requests\CreateUploadRequest;
use Illuminate\Support\Str;
use Mockery;
use Illuminate\Contracts\Auth\Access\Gate;

class CreateUploadRequestTest extends AbstractTestCase
{

    /** @test */
    public function request_authorize_calls_the_gate()
    {
        $requestId = Str::random(60);
        $args = ['id' => $requestId, 'filename' => 'test.pdf', 'filesize' => 5];

        $user = new FakeUser();

        $request = CreateUploadRequest::createFromBase(\Symfony\Component\HttpFoundation\Request::create('/uploadqueue', 'POST', $args));

        $request->setUserResolver(function() use($user){
            return $user;
        });
        
        $this->assertTrue($request->authorize());

        $user->assertCanCalled('upload-via-tus');
    }

}
