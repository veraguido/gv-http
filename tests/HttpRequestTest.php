<?php

namespace Tests;

use Gvera\Helpers\config\Config;
use Gvera\Helpers\fileSystem\FileManager;
use Gvera\Helpers\http\HttpRequest;
use Gvera\Helpers\http\HttpRequestValidator;
use Gvera\Helpers\validation\ValidationService;

class HttpRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function testMethods()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['asd'] = 'qwe';
        $_POST['qwe'] = 'asd';
        $fileManager = new FileManager(new Config());
        $validator = new HttpRequestValidator(new ValidationService());
        $firstRequest = new HttpRequest($fileManager, $validator);

        $this->assertTrue($firstRequest->isGet());
        $this->assertTrue($firstRequest->getParameter('asd') === 'qwe');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $secondRequest = new HttpRequest($fileManager, $validator);
        $this->assertTrue($secondRequest->isPost());
        $this->assertTrue($secondRequest->getRequestType() === HttpRequest::POST);
        $this->assertTrue($secondRequest->getParameter('qwe') === 'asd');
        $secondRequest->setParameter('qwe', "fff");
        $this->assertTrue($secondRequest->getParameter('qwe') === 'fff');

        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $thirdRequest = new HttpRequest($fileManager, $validator);
        $this->assertTrue($thirdRequest->isPatch());

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $forthRequest = new HttpRequest($fileManager, $validator);
        $this->assertTrue($forthRequest->isPut());

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        $this->assertTrue($forthRequest->isAjax());

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $fifthRequest = new HttpRequest($fileManager, $validator);
        $this->assertTrue($fifthRequest->isDelete());
    }

    /**
     * @test
     */
    public function testGetAuthDetails()
    {
        $fileManager = new FileManager(new Config());
        $validator = new HttpRequestValidator(new ValidationService());
        $firstRequest = new HttpRequest($fileManager, $validator);

        $this->assertNull($firstRequest->getAuthDetails());

        $_SERVER['PHP_AUTH_USER'] = 'testUser';
        $_SERVER['PHP_AUTH_PW'] = 'testPassword';

        $authDetails = $firstRequest->getAuthDetails();
        $this->assertTrue($authDetails->getUsername() === 'testUser');
        $this->assertTrue($authDetails->getPassword() === 'testPassword');
    }

    /**
     * @test
     */
    public function testParameters()
    {
        $fileManager = new FileManager(new Config());
        $validator = new HttpRequestValidator(new ValidationService());
        $firstRequest = new HttpRequest($fileManager, $validator);

        $_REQUEST = ['asd'=>'qwe', 'zxc' => 'cvb'];

        $this->assertIsArray($firstRequest->getParametersFromRequest());
    }

    /**
     * @test
     */
    public function testIP()
    {
        $_SERVER['REMOTE_ADDR'] = '1.0.0.0';
        $fileManager = new FileManager(new Config());
        $validator = new HttpRequestValidator(new ValidationService());
        $firstRequest = new HttpRequest($fileManager, $validator);

        $this->assertTrue($firstRequest->getIP() === '1.0.0.0');
    }
}