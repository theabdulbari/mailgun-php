<?php

/*
 * Copyright (C) 2013 Mailgun
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Mailgun\Tests\Api;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mailgun\Mailgun;
use Mailgun\Model\ApiResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Contributors of https://github.com/KnpLabs/php-github-api
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Private Mailgun API key.
     *
     * @var string
     */
    protected $apiPrivKey;

    /**
     * Public Mailgun API key.
     *
     * @var string
     */
    protected $apiPubKey;

    /**
     * Domain used for API testing.
     *
     * @var string
     */
    protected $testDomain;

    private $requestMethod;
    private $requestUri;
    private $requestHeaders;
    private $requestBody;

    private $httpResponse;
    private $hydratedResponse;
    private $hydrateClass;

    protected function setUp()
    {
        $this->apiPrivKey = getenv('MAILGUN_PRIV_KEY');
        $this->apiPubKey = getenv('MAILGUN_PUB_KEY');
        $this->testDomain = getenv('MAILGUN_DOMAIN');
        $this->reset();
    }

    abstract protected function getApiClass();

    protected function getApiMock($httpClient = null, $requestClient = null, $hydrator = null)
    {
        if (null === $httpClient) {
            $httpClient = $this->getMockBuilder('Http\Client\HttpClient')
                ->setMethods(['sendRequest'])
                ->getMock();
            $httpClient
                ->method('sendRequest')
                ->willReturn($this->httpResponse === null ? new Response() : $this->httpResponse);
        }

        if (null === $requestClient) {
            $requestClient = $this->getMockBuilder('Mailgun\RequestBuilder')
                ->setMethods(['create'])
                ->getMock();
            $requestClient->method('create')
                ->with(
                    $this->callback([$this, 'validateRequestMethod']),
                    $this->callback([$this, 'validateRequestUri']),
                    $this->callback([$this, 'validateRequestHeaders']),
                    $this->callback([$this, 'validateRequestBody'])
                )
                ->willReturn(new Request('GET', '/'));
        }

        if (null === $hydrator && null === $this->httpResponse) {
            $hydrator = $this->getMockBuilder('Mailgun\Hydrator\Hydrator')
                ->setMethods(['hydrate'])
                ->getMock();

            $hydratorModelClass = $this->hydrateClass;
            $hydrateMethod = $hydrator->method('hydrate')
                ->with(
                    $this->callback(function ($response) {
                        return $response instanceof ResponseInterface;
                    }),
                    $this->callback(function ($class) use ($hydratorModelClass) {
                        return $hydratorModelClass === null || $class === $hydratorModelClass;
                    }));

            if (null !== $this->hydratedResponse) {
                $hydrateMethod->willReturn($this->hydratedResponse);
            }
        }

        $class = $this->getApiClass();
        return new $class($httpClient, $requestClient, $hydrator);
    }

    public function validateRequestMethod($method)
    {
        return $this->requestMethod === null || $method === $this->requestMethod;
    }

    public function validateRequestUri($uri)
    {
        return $this->requestUri === null || $uri === $this->requestUri;
    }

    public function validateRequestHeaders($headers)
    {
        if (null === $this->requestHeaders) {
            return true;
        }

        return $this->requestHeaders == $headers;
    }

    public function validateRequestBody($body)
    {
        return $this->requestMethod === null || $body === $this->requestBody;
    }

    protected function getMailgunClient()
    {
        return Mailgun::create($this->apiPrivKey);
    }

    protected function reset()
    {
        $this->httpResponse = null;
        $this->hydratedResponse = null;
        $this->requestMethod = null;
        $this->requestUri = null;
        $this->requestHeaders = null;
        $this->requestBody = null;
        $this->hydrateClass = null;
    }

    /**
     * Set a response that you want to client to respond with.
     */
    public function setHttpResponse(ResponseInterface $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }

    /**
     * The data you want the hydrator to return.
     *
     * @param mixed $hydratedResponse
     */
    public function setHydratedResponse($hydratedResponse)
    {
        $this->hydratedResponse = $hydratedResponse;
    }

    /**
     * Set request http method
     * @param string $httpMethod
     */
    public function setRequestMethod($httpMethod)
    {
        $this->requestMethod = $httpMethod;
    }

    /**
     * @param string $requestUri
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * @param array $requestHeaders
     */
    public function setRequestHeaders(array $requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @param mixed $requestBody
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
    }

    /**
     * The class we should hydrate to
     * @param string $hydrateClass
     */
    public function setHydrateClass($hydrateClass)
    {
        $this->hydrateClass = $hydrateClass;
    }


}
