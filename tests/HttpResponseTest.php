<?php

namespace Tests;

use Gvera\Helpers\http\HttpResponse;
use Gvera\Helpers\http\JSONResponse;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    /**
     * @test
     */
    public function testResponse()
    {
        $response = new HttpResponse();
        $this->expectOutputString('{"asd":"asd"}');
        $response->response(new JSONResponse(['asd' => 'asd']));
    }
}
