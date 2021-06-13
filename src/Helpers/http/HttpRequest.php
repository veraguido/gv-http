<?php

namespace Gvera\Helpers\http;

use Exception;
use Gvera\Exceptions\InvalidFileTypeException;
use Gvera\Exceptions\NotFoundException;
use Gvera\Helpers\fileSystem\File;
use Gvera\Helpers\fileSystem\FileManager;
use Gvera\Models\BasicAuthenticationDetails;
use ReflectionException;

/**
 * Class HttpRequest
 * @package Gvera\Helpers\http
 * This is a request wrapper to manage params making an abstraction from the request type
 */
class HttpRequest
{

    private string $requestType;
    private array $requestParams = array();
    private FileManager $fileManager;
    private HttpRequestValidator $httpRequestValidator;

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = "PATCH";
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';


    public function __construct(FileManager $fileManager, HttpRequestValidator $httpRequestValidator)
    {
        $this->fileManager = $fileManager;
        $this->httpRequestValidator = $httpRequestValidator;
        $this->requestType =  $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getParameter($name)
    {
        if (isset($this->requestParams[$name])) {
            return $this->requestParams[$name];
        }

        $req = strtolower($this->requestType);
        return $this->$req($name);
    }

    /**
     * @param null $name
     * @return mixed|null
     */
    public function get($name)
    {
        return filter_var($_GET[$name], FILTER_SANITIZE_STRING);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function post($name = null)
    {
        return filter_var($_POST[$name], FILTER_SANITIZE_STRING);
    }

    /**
     * @param null $name
     * @return mixed
     * @throws NotFoundException
     */
    public function patch($name = null)
    {
        return $this->getParameterFromStream($name);
    }

    /**
     * @param null $name
     * @return mixed
     * @throws NotFoundException
     */
    public function put($name = null)
    {
        return $this->getParameterFromStream($name);
    }

    /**
     * @param null $name
     * @return mixed
     * @throws NotFoundException
     */
    public function delete($name = null)
    {
        return $this->getParameterFromStream($name);
    }

    /**
     * @param string $name
     * @return string
     * @throws NotFoundException
     */
    private function getParameterFromStream(string $name): string
    {
            $streamContent = [];
            parse_str(file_get_contents("php://input"), $streamContent);

        if (!isset($streamContent[$name])) {
            throw new NotFoundException('parameter not found');
        }

        return $streamContent[$name];
    }

    public function getParametersFromStream(): array
    {
        $streamContent = [];
        parse_str(file_get_contents("php://input"), $streamContent);
        return $streamContent;
    }

    public function getParametersFromRequest(): array
    {
        return $_REQUEST;
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->requestType == self::POST;
    }

    /**
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->requestType == self::GET;
    }

    /**
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->requestType == self::PUT;
    }

    /**
     * @return boolean
     */
    public function isPatch(): bool
    {
        return $this->requestType == self::PATCH;
    }

    /**
     * @return boolean
     */
    public function isDelete(): bool
    {
        return $this->requestType == self::DELETE;
    }

    /**
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function setParameter($key, $value)
    {
        $this->requestParams[$key] = $value;
    }

    /**
     * @return boolean
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * @param $directory
     * @param File $file
     * @return bool
     * @throws NotFoundException
     * @throws InvalidFileTypeException
     */
    public function moveFileToDirectory($directory, File $file): bool
    {
        return $this->fileManager->saveToFileSystem($directory, $file);
    }

    /**
     * @param $propertyName
     * @param string|null $changedName
     * @return File
     * @throws NotFoundException
     */
    public function getFileByPropertyName($propertyName, ?string $changedName = null): File
    {
        $this->fileManager->buildFilesFromSource($_FILES, $changedName);
        return $this->fileManager->getByName($propertyName);
    }

    public function getAuthDetails(): ?BasicAuthenticationDetails
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            return null;
        }

        return new BasicAuthenticationDetails($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    public function getIP(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return bool
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws Exception
     */
    public function validate(): bool
    {
        $traces = debug_backtrace();

        if (!isset($traces[1])) {
            throw new Exception('incorrect method calling validate');
        }
        $method = $traces[1]['function'];
        $fullClassPath = $traces[1]['class'];
        $reflectionClass = new \ReflectionClass($fullClassPath);
        $controllersClassName = $reflectionClass->getShortName();

        $fields =
            $this->isGet() || $this->isPost() ?
            $this->getParametersFromRequest() :
            $this->getParametersFromStream();

        return $this->httpRequestValidator->validate($controllersClassName, $method, $fields, getallheaders());
    }

    public function getAuthorizationHeader(): ?string
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

}
