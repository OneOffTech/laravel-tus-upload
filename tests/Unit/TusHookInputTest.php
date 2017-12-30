<?php

namespace Tests\Unit;

use Tests\AbstractTestCase;
use OneOffTech\TusUpload\Console\TusHookInput;

class TusHookInputTest extends AbstractTestCase
{

    private function generateHookPayload($requestId, $tusId = '', $offset = 0)
    {
        return sprintf('{' .
                          '"ID": "%2$s",' .
                          '"Size": 46205,' .
                          '"Offset": %3$s,' .
                          '"IsFinal": false,' .
                          '"IsPartial": false,' .
                          '"PartialUploads": null,' .
                          '"MetaData": {' .
                          '  "filename": "test.png",' .
                          '  "token": "AAAAAAAAAAA",' .
                          '  "upload_request_id": "%1$s"' .
                          '}' .
                        '}', $requestId, $tusId, $offset);
    }

    /** @test */
    public function request_is_created()
    {
        $hook_content = $this->generateHookPayload('14b1c4c77771671a8479bc0444bbc5ce', 'aaaaaaa', 100);

        $request = TusHookInput::create($hook_content);

        $this->assertInstanceOf(TusHookInput::class, $request);
    }

    /** @test */
    public function request_has_input()
    {

        $request_id = '14b1c4c77771671a8479bc0444bbc5ce';
        $tus_id = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

        $hook_content = $this->generateHookPayload($request_id, $tus_id, 100);

        $request = TusHookInput::create($hook_content);

        $this->assertTrue($request->has('ID'));
        $this->assertTrue($request->has('Size'));
        $this->assertTrue($request->has('Offset'));
        $this->assertTrue($request->has('MetaData.upload_request_id'));
        $this->assertTrue($request->has('MetaData.token'));
        $this->assertTrue($request->has('MetaData.filename'));

        $this->assertEquals($request_id, $request->id());
        $this->assertEquals($tus_id, $request->tusId());

    }

    /** @test */
    public function request_input_is_retrievable()
    {
        $request_id = '14b1c4c77771671a8479bc0444bbc5ce';
        $tus_id = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $offset = 100;

        $hook_content = $this->generateHookPayload($request_id, $tus_id, $offset);

        $request = TusHookInput::create($hook_content);

        $this->assertEquals('test.png', $request->input('MetaData.filename'));
        $this->assertEquals('AAAAAAAAAAA', $request->input('MetaData.token'));
        $this->assertEquals($tus_id, $request->input('ID'));
        $this->assertEquals($offset, $request->input('Offset'));
        $this->assertEquals(46205, $request->input('Size'));
    }

}
