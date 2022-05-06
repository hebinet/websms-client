<?php

namespace WebSms\Tests\Traits;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait HandlerStackTrait
{
    protected function couldntConnectHandler(): HandlerStack
    {
        return HandlerStack::create(new MockHandler(
            [
                new RequestException('Error Communicating with Server', new Request('GET', 'test')),
            ]));
    }

    protected function authenticationFailedHandler(): HandlerStack
    {
        return HandlerStack::create(new MockHandler(
            [
                new Response(401, []),
            ]));
    }

    protected function httpErrorHandler(): HandlerStack
    {
        return HandlerStack::create(new MockHandler(
            [
                new Response(204, ['Content-Type' => 'application/json']),
            ]));
    }

    protected function noJsonResponseHandler(): HandlerStack
    {
        return HandlerStack::create(new MockHandler(
            [
                new Response(200, ['Content-Type' => 'text/plain'], 'test'),
            ]));
    }

    protected function wrongApiStatusCodeHandler(): HandlerStack
    {
        $wrongApiStatusCode = new \stdClass();
        $wrongApiStatusCode->statusCode = 1999;
        $wrongApiStatusCode->statusMessage = 'Some error appeared';

        return HandlerStack::create(new MockHandler(
            [
                new Response(200,
                    ['Content-Type' => 'application/json'],
                    json_encode($wrongApiStatusCode, JSON_THROW_ON_ERROR)),
            ]));
    }

    protected function successHandler(): HandlerStack
    {
        $successfulResponse = new \stdClass();
        $successfulResponse->statusCode = 2000;
        $successfulResponse->statusMessage = 'OK';
        $successfulResponse->transferId = '12345';
        $successfulResponse->clientMessageId = '67890';

        return HandlerStack::create(new MockHandler(
            [
                new Response(200, ['Content-Type' => 'application/json'],
                    json_encode($successfulResponse, JSON_THROW_ON_ERROR)),
            ]));
    }
}
